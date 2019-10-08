<?php

namespace AmeliaBooking\Infrastructure\WP\Integrations\WooCommerce;

use AmeliaBooking\Application\Commands\CommandResult;
use AmeliaBooking\Application\Services\Booking\BookingApplicationService;
use AmeliaBooking\Domain\Collection\Collection;
use AmeliaBooking\Domain\Entity\Bookable\Service\Extra;
use AmeliaBooking\Domain\Entity\Bookable\Service\Service;
use AmeliaBooking\Domain\Entity\Coupon\Coupon;
use AmeliaBooking\Domain\Entity\Entities;
use AmeliaBooking\Domain\Entity\User\Provider;
use AmeliaBooking\Domain\Services\DateTime\DateTimeService;
use AmeliaBooking\Domain\Services\Settings\SettingsService;
use AmeliaBooking\Domain\ValueObjects\String\PaymentStatus;
use AmeliaBooking\Infrastructure\Common\Container;
use AmeliaBooking\Infrastructure\Repository\Booking\Appointment\AppointmentRepository;
use AmeliaBooking\Infrastructure\Repository\User\ProviderRepository;
use AmeliaBooking\Infrastructure\WP\EventListeners\Booking\Appointment\AppointmentAddedEventHandler;
use AmeliaBooking\Infrastructure\WP\Translations\FrontendStrings;
use Interop\Container\Exception\ContainerException;

/**
 * Class WooCommerceService
 *
 * @package AmeliaBooking\Infrastructure\WP\Integrations\WooCommerce
 */
class WooCommerceService
{
    /** @var Container $container */
    private static $container;

    /** @var SettingsService $settingsService */
    private static $settingsService;

    /** @var array $checkout_info */
    protected static $checkout_info = [];

    const AMELIA = 'ameliabooking';

    /**
     * Init
     *
     * @param $settingsService
     *
     * @throws ContainerException
     */
    public static function init($settingsService)
    {
        self::setContainer(require AMELIA_PATH . '/src/Infrastructure/ContainerConfig/container.php');
        self::$settingsService = $settingsService;

        add_action('woocommerce_before_cart_contents', [self::class, 'beforeCartContents'], 10, 0);
        add_filter('woocommerce_get_item_data', [self::class, 'getItemData'], 10, 2);
        add_filter('woocommerce_cart_item_price', [self::class, 'cartItemPrice'], 10, 3);
        add_filter('woocommerce_checkout_get_value', [self::class, 'checkoutGetValue'], 10, 2);
        add_filter('woocommerce_add_order_item_meta', [self::class, 'addOrderItemMeta'], 10, 3);
        add_filter('woocommerce_order_item_meta_end', [self::class, 'orderItemMeta'], 10, 3);
        add_filter('woocommerce_after_order_itemmeta', [self::class, 'orderItemMeta'], 10, 3);

        add_action('woocommerce_order_status_completed', [self::class, 'paymentComplete'], 10, 1);
        add_action('woocommerce_order_status_on-hold', [self::class, 'paymentComplete'], 10, 1);
        add_action('woocommerce_order_status_processing', [self::class, 'paymentComplete'], 10, 1);

        add_action('woocommerce_before_checkout_process', [self::class, 'beforeCheckoutProcess'], 10, 1);
    }

    /**
     * Set Amelia Container
     *
     * @param $container
     */
    public static function setContainer($container)
    {
        self::$container = $container;
    }

    /**
     * Get cart page
     *
     * @return string
     */
    public static function getCartUrl()
    {
        return wc_get_cart_url();
    }

    /**
     * Get WooCommerce Cart
     */
    private static function getWooCommerceCart()
    {
        return wc()->cart;
    }

    /**
     * Is WooCommerce enabled
     *
     * @return string
     */
    public static function isEnabled()
    {
        return class_exists('WooCommerce');
    }

    /**
     * Get product id from settings
     *
     * @return int
     */
    private static function getProductIdFromSettings()
    {
        return self::$settingsService->getCategorySettings('payments')['wc']['productId'];
    }

    /**
     * Validate appointment booking
     *
     * @param array $data
     *
     * @return bool
     */
    private static function validateBooking($data)
    {
        try {
            $errorMessage = '';

            if ($data) {
                /** @var CommandResult $result */
                $result = new CommandResult();

                /** @var BookingApplicationService $bookingAS */
                $bookingAS = self::$container->get('application.booking.booking.service');

                /** @var AppointmentRepository $appointmentRepo */
                $appointmentRepo = self::$container->get('domain.booking.appointment.repository');

                /** @var AppointmentRepository $appointmentRepo */
                $bookingAS->processBooking($result, $appointmentRepo, $data, true, true, false);

                if ($result->getResult() === CommandResult::RESULT_ERROR) {
                    if (isset($result->getData()['emailError'])) {
                        $errorMessage = FrontendStrings::getCommonStrings()['email_exist_error'];
                    }

                    if (isset($result->getData()['couponUnknown'])) {
                        $errorMessage = FrontendStrings::getCommonStrings()['coupon_unknown'];
                    }

                    if (isset($result->getData()['couponInvalid'])) {
                        $errorMessage = FrontendStrings::getCommonStrings()['coupon_invalid'];
                    }

                    if (isset($result->getData()['customerAlreadyBooked'])) {
                        $errorMessage = FrontendStrings::getCommonStrings()['customer_already_booked'];
                    }

                    if (isset($result->getData()['timeSlotUnavailable'])) {
                        $errorMessage = FrontendStrings::getCommonStrings()['time_slot_unavailable'];
                    }

                    return $errorMessage ?
                        "$errorMessage (<strong>{$data['serviceName']}</strong> {$data['bookingStart']}). " : '';
                }

                return '';
            }
        } catch (ContainerException $e) {
            return '';
        } catch (\Exception $e) {
            return '';
        }
    }

    /**
     * Save appointment booking
     *
     * @param array $data
     */
    private static function saveBooking($data)
    {
        try {
            $result = new CommandResult();

            /** @var BookingApplicationService $bookingAS */
            $bookingAS = self::$container->get('application.booking.booking.service');

            /** @var AppointmentRepository $appointmentRepo */
            $appointmentRepo = self::$container->get('domain.booking.appointment.repository');

            if ($bookingData = $bookingAS->processBooking($result, $appointmentRepo, $data, false, false, true)) {
                $result->setData([
                    Entities::APPOINTMENT => $bookingData['appointment']->toArray()
                ]);

                AppointmentAddedEventHandler::handle($result, self::$container);
            }
        } catch (ContainerException $e) {
        } catch (\Exception $e) {
        }
    }

    /**
     * Get existing, or new created product id
     *
     * @param $postId
     *
     * @return int|\WP_Error
     */
    public static function getIdForExistingOrNewProduct($postId)
    {
        $createProduct = true;

        foreach (get_posts(['post_type' => 'product']) as $product) {
            if ($product->ID === $postId) {
                $createProduct = false;
            }
        }

        if ($createProduct) {
            $postId = wp_insert_post([
                'post_author'  => get_current_user(),
                'post_title'   => FrontendStrings::getCommonStrings()['wc_product_name'],
                'post_content' => '',
                'post_status'  => 'publish',
                'post_type'    => 'product',
            ]);

            wp_set_object_terms($postId, 'simple', 'product_type');
            wp_set_object_terms($postId, ['exclude-from-catalog', 'exclude-from-search'], 'product_visibility');
            update_post_meta($postId, '_visibility', 'hidden');
            update_post_meta($postId, '_stock_status', 'instock');
            update_post_meta($postId, 'total_sales', '0');
            update_post_meta($postId, '_downloadable', 'no');
            update_post_meta($postId, '_virtual', 'yes');
            update_post_meta($postId, '_regular_price', 0);
            update_post_meta($postId, '_sale_price', '');
            update_post_meta($postId, '_purchase_note', '');
            update_post_meta($postId, '_featured', 'no');
            update_post_meta($postId, '_weight', '');
            update_post_meta($postId, '_length', '');
            update_post_meta($postId, '_width', '');
            update_post_meta($postId, '_height', '');
            update_post_meta($postId, '_sku', '');
            update_post_meta($postId, '_product_attributes', array());
            update_post_meta($postId, '_sale_price_dates_from', '');
            update_post_meta($postId, '_sale_price_dates_to', '');
            update_post_meta($postId, '_price', 0);
            update_post_meta($postId, '_sold_individually', 'yes');
            update_post_meta($postId, '_manage_stock', 'no');
            update_post_meta($postId, '_backorders', 'no');
            update_post_meta($postId, '_stock', '');
        }

        return $postId;
    }

    /**
     * Fetch entity if not in cache
     *
     * @param $data
     *
     * @return array
     */
    private static function getEntity($data)
    {
        if (!Cache::get($data['providerId'], $data['serviceId'])) {
            $ameliaEntitiesIds[] = [
                'serviceId'  => $data['serviceId'],
                'providerId' => $data['providerId'],
                'couponId'   => $data['couponId'],
            ];

            if ($ameliaEntitiesIds) {
                self::fetchEntities($ameliaEntitiesIds);
            }
        }

        return Cache::get($data['providerId'], $data['serviceId']);
    }

    /**
     * Get payment amount for service
     *
     * @param $wcItemAmeliaCache
     * @param $booking
     *
     * @return float
     */
    private static function getPaymentAmount($wcItemAmeliaCache, $booking)
    {
        $extras = [];

        foreach ((array)$wcItemAmeliaCache['bookings'][0]['extras'] as $extra) {
            $extras[] = [
                'price'    => $booking['extras'][$extra['extraId']]['price'],
                'quantity' => $extra['quantity']
            ];
        }

        $price = (float)$booking['service']['price'] * $wcItemAmeliaCache['bookings'][0]['persons'];

        foreach ($extras as $extra) {
            $price += (float)$extra['price'] * $wcItemAmeliaCache['bookings'][0]['persons'] * $extra['quantity'];
        }

        if ($wcItemAmeliaCache['couponId'] && isset($booking['coupons'][$wcItemAmeliaCache['couponId']])) {
            $price -= $price / 100 *
                ($wcItemAmeliaCache['couponId'] ? $booking['coupons'][$wcItemAmeliaCache['couponId']]['discount'] : 0) +
                ($wcItemAmeliaCache['couponId'] ? $booking['coupons'][$wcItemAmeliaCache['couponId']]['deduction'] : 0);
        }

        return $price;
    }


    /**
     * Fetch entities from DB and set them into cache
     *
     * @param $ameliaEntitiesIds
     */
    private static function fetchEntities($ameliaEntitiesIds)
    {
        try {
            /** @var ProviderRepository $providerRepository */
            $providerRepository = self::$container->get('domain.users.providers.repository');

            /** @var Collection $providers */
            $providers = $providerRepository->getWithServicesAndExtras($ameliaEntitiesIds);

            $bookings = [];

            foreach ((array)$providers->keys() as $providerKey) {
                /** @var Provider $provider */
                $provider = $providers->getItem($providerKey);

                /** @var Collection $services */
                $services = $provider->getServiceList();

                foreach ((array)$services->keys() as $serviceKey) {
                    /** @var Service $service */
                    $service = $services->getItem($serviceKey);

                    /** @var Collection $extras */
                    $extras = $service->getExtras();

                    $bookings[$providerKey][$serviceKey] = [
                        'firstName' => $provider->getFirstName()->getValue(),
                        'lastName'  => $provider->getLastName()->getValue(),
                        'service'   => [
                            'name'  => $service->getName()->getValue(),
                            'price' => $service->getPrice()->getValue(),
                        ],
                        'coupons'   => [],
                        'extras'    => []
                    ];

                    foreach ((array)$extras->keys() as $extraKey) {
                        /** @var Extra $extra */
                        $extra = $extras->getItem($extraKey);

                        $bookings[$providerKey][$serviceKey]['extras'][$extra->getId()->getValue()] = [
                            'price' => $extra->getPrice()->getValue(),
                            'name'  => $extra->getName()->getValue(),
                        ];
                    }

                    /** @var Collection $coupons */
                    $coupons = $service->getCoupons();

                    foreach ((array)$coupons->keys() as $couponKey) {
                        /** @var Coupon $coupon */
                        $coupon = $coupons->getItem($couponKey);

                        $bookings[$providerKey][$serviceKey]['coupons'][$coupon->getId()->getValue()] = [
                            'deduction' => $coupon->getDeduction()->getValue(),
                            'discount'  => $coupon->getDiscount()->getValue(),
                        ];
                    }
                }
            }

            Cache::add($bookings);
        } catch (\Exception $e) {
        } catch (ContainerException $e) {
        }
    }

    /**
     * Process data for amelia cart items
     *
     * @param bool $inspectData
     */
    private static function processCart($inspectData)
    {
        $wooCommerceCart = self::getWooCommerceCart();

        $ameliaEntitiesIds = [];

        if (!Cache::getAll()) {
            foreach ($wooCommerceCart->get_cart() as $wc_key => $wc_item) {
                if (isset($wc_item[self::AMELIA])) {
                    if ($inspectData && ($errorMessage = self::validateBooking($wc_item[self::AMELIA]))) {
                        wc_add_notice(
                            $errorMessage . FrontendStrings::getCommonStrings()['wc_appointment_is_removed'],
                            'error'
                        );
                        $wooCommerceCart->remove_cart_item($wc_key);
                    }

                    $ameliaEntitiesIds[] = [
                        'serviceId'  => $wc_item[self::AMELIA]['serviceId'],
                        'providerId' => $wc_item[self::AMELIA]['providerId'],
                        'couponId'   => $wc_item[self::AMELIA]['couponId'],
                    ];
                }
            }

            if ($ameliaEntitiesIds) {
                self::fetchEntities($ameliaEntitiesIds);
            }
        }

        foreach ($wooCommerceCart->get_cart() as $wc_key => $wc_item) {
            if (isset($wc_item[self::AMELIA])) {
                /** @var \WC_Product $wc_item ['data'] */
                $wc_item['data']->set_price(
                    self::getPaymentAmount(
                        $wc_item[self::AMELIA],
                        self::getEntity($wc_item[self::AMELIA])
                    )
                );
            }
        }

        if (isset($wc_item[self::AMELIA])) {
            $wooCommerceCart->calculate_totals();

            wc_print_notices();
        }
    }

    /**
     * Add appointment booking to cart
     *
     * @param $data
     *
     * @return boolean
     * @throws \Exception
     */
    public static function addToCart($data)
    {
        $wooCommerceCart = self::getWooCommerceCart();

        foreach ($wooCommerceCart->get_cart() as $wc_key => $wc_item) {
            if (isset($wc_item[self::AMELIA])) {
                $wooCommerceCart->remove_cart_item($wc_key);
            }
        }

        $wooCommerceCart->add_to_cart(self::getProductIdFromSettings(), 1, '', [], [self::AMELIA => $data]);

        return true;
    }

    /**
     * Verifies the availability of all appointments that are in the cart
     */
    public static function beforeCartContents()
    {
        self::processCart(true);
    }

    /**
     * Get item data for cart.
     *
     * @param $other_data
     * @param $wc_item
     *
     * @return array
     * @throws \Exception
     */
    public static function getItemData($other_data, $wc_item)
    {
        if (isset($wc_item[self::AMELIA])) {
            if (self::getWooCommerceCart()) {
                self::processCart(false);
            }

            /** @var array $booking */
            $booking = self::getEntity($wc_item[self::AMELIA]);

            $bookingStart = \DateTime::createFromFormat('Y-m-d H:i', $wc_item[self::AMELIA]['bookingStart'])->format(
                self::$settingsService->getCategorySettings('wordpress')['dateFormat'] . ' ' .
                self::$settingsService->getCategorySettings('wordpress')['timeFormat']
            );

            $utcOffset = $wc_item[self::AMELIA]['bookings'][0]['utcOffset'];
            $clientZoneBookingStart = null;

            $timeInfo = [
                '<hr>',
                '<strong>' . FrontendStrings::getCommonStrings()['time_colon'] . '</strong> '
                . $bookingStart,
            ];

            if ($utcOffset !== null) {
                $clientZoneBookingStart = DateTimeService::getClientUtcCustomDateTimeObject(
                    DateTimeService::getCustomDateTimeInUtc($wc_item[self::AMELIA]['bookingStart']),
                    $utcOffset
                )->format(
                    self::$settingsService->getCategorySettings('wordpress')['dateFormat'] . ' ' .
                    self::$settingsService->getCategorySettings('wordpress')['timeFormat']
                );

                $utcString = '(UTC' . ($utcOffset < 0 ? '-' : '+') .
                    sprintf('%02d:%02d', floor(abs($utcOffset) / 60), abs($utcOffset) % 60) . ')';

                $timeInfo[] = '<strong>' . FrontendStrings::getCommonStrings()['client_time_colon'] . '</strong> '
                    . $utcString . $clientZoneBookingStart;
            }

            $customFieldsInfo = [];

            $customFieldsArray = $wc_item[self::AMELIA]['bookings'][0]['customFields'] ?
                json_decode($wc_item[self::AMELIA]['bookings'][0]['customFields'], true) : [];

            foreach ((array)$customFieldsArray as $customField) {
                if (is_array($customField['value'])) {
                    $customFieldsInfo[] = '' . $customField['label'] . ': ' . implode(', ', $customField['value']);
                } else {
                    $customFieldsInfo[] = '' . $customField['label'] . ': ' . $customField['value'];
                }
            }


            $extrasInfo = [];

            foreach ((array)$wc_item[self::AMELIA]['bookings'][0]['extras'] as $extra) {
                $extrasInfo[] = $booking['extras'][$extra['extraId']]['name'] . ' (x' . $extra['quantity'] . ')';
            }

            $couponUsed = [];

            if ($wc_item[self::AMELIA]['couponId']) {
                $couponUsed = [
                    '<strong>' . FrontendStrings::getCommonStrings()['coupon_used'] . '</strong>'
                ];
            }

            $other_data[] = [
                'name'  => FrontendStrings::getCommonStrings()['appointment_info'],
                'value' => implode(
                    PHP_EOL . PHP_EOL,
                    array_merge(
                        $timeInfo,
                        [
                            '<strong>' . self::$settingsService->getCategorySettings('labels')['service']
                            . ':</strong> ' . $booking['service']['name'],
                            '<strong>' . self::$settingsService->getCategorySettings('labels')['employee']
                            . ':</strong> ' . $booking['firstName'] . ' ' . $booking['lastName'],
                            '<strong>' . FrontendStrings::getCommonStrings()['total_number_of_persons'] . '</strong> '
                            . $wc_item[self::AMELIA]['bookings'][0]['persons'],
                        ],
                        $extrasInfo ? array_merge(
                            [
                                '<strong>' . FrontendStrings::getCatalogStrings()['extras'] . ':</strong>'
                            ],
                            $extrasInfo
                        ) : [],
                        $customFieldsInfo ? array_merge(
                            [
                                '<strong>' . FrontendStrings::getCommonStrings()['custom_fields'] . ':</strong>'
                            ],
                            $customFieldsInfo
                        ) : [],
                        $couponUsed
                    )
                )
            ];
        }

        return $other_data;
    }

    /**
     * Get cart item price.
     *
     * @param $product_price
     * @param $wc_item
     * @param $cart_item_key
     *
     * @return mixed
     */
    public static function cartItemPrice($product_price, $wc_item, $cart_item_key)
    {
        if (isset($wc_item[self::AMELIA])) {
            $product_price = wc_price(
                self::getPaymentAmount(
                    $wc_item[self::AMELIA],
                    self::getEntity($wc_item[self::AMELIA])
                )
            );
        }

        return $product_price;
    }

    /**
     * Assign checkout value from appointment.
     *
     * @param $null
     * @param $field_name
     *
     * @return string|null
     */
    public static function checkoutGetValue($null, $field_name)
    {
        $wooCommerceCart = self::getWooCommerceCart();

        self::processCart(false);

        if (empty(self::$checkout_info)) {
            foreach ($wooCommerceCart->get_cart() as $wc_key => $wc_item) {
                if (array_key_exists(self::AMELIA, $wc_item)) {
                    self::$checkout_info = [
                        'billing_first_name' => $wc_item[self::AMELIA]['bookings'][0]['customer']['firstName'],
                        'billing_last_name'  => $wc_item[self::AMELIA]['bookings'][0]['customer']['lastName'],
                        'billing_email'      => $wc_item[self::AMELIA]['bookings'][0]['customer']['email'],
                        'billing_phone'      => $wc_item[self::AMELIA]['bookings'][0]['customer']['phone']
                    ];
                    break;
                }
            }
        }

        if (array_key_exists($field_name, self::$checkout_info)) {
            return self::$checkout_info[$field_name];
        }

        return null;
    }

    /**
     * Add order item meta.
     *
     * @param $item_id
     * @param $values
     * @param $wc_key
     */
    public static function addOrderItemMeta($item_id, $values, $wc_key)
    {
        if (isset($values[self::AMELIA])) {
            wc_update_order_item_meta($item_id, self::AMELIA, $values[self::AMELIA]);
        }
    }

    /**
     * Print appointment details inside order items in the backend.
     *
     * @param int $item_id
     */
    public static function orderItemMeta($item_id)
    {
        $data = wc_get_order_item_meta($item_id, self::AMELIA);

        if ($data) {
            $other_data = self::getItemData([], [self::AMELIA => $data]);

            echo '<br/>' . $other_data[0]['name'] . '<br/>' . nl2br($other_data[0]['value']);
        }
    }

    /**
     * Before checkout process
     *
     * @param $array
     *
     * @throws \Exception
     */
    public static function beforeCheckoutProcess($array)
    {
        $wooCommerceCart = self::getWooCommerceCart();

        foreach ($wooCommerceCart->get_cart() as $wc_key => $wc_item) {
            if (isset($wc_item[self::AMELIA])) {
                if ($errorMessage = self::validateBooking($wc_item[self::AMELIA])) {
                    $cartUrl = self::getCartUrl();
                    $removeAppointmentMessage = FrontendStrings::getCommonStrings()['wc_appointment_is_removed'];

                    throw new \Exception($errorMessage . "<a href='{$cartUrl}'>{$removeAppointmentMessage}</a>");
                }
            }
        }
    }

    /**
     * Do bookings after checkout.
     *
     * @param $order_id
     */
    public static function paymentComplete($order_id)
    {
        $order = new \WC_Order($order_id);

        foreach ($order->get_items() as $item_id => $order_item) {
            $data = wc_get_order_item_meta($item_id, self::AMELIA);

            try {
                if ($data && !isset($data['processed'])) {
                    $data['payment']['gatewayTitle'] = $order->get_payment_method_title();
                    $data['payment']['amount'] = 0;
                    $data['payment']['status'] = $order->get_payment_method() === 'cod' ?
                        PaymentStatus::PENDING : PaymentStatus::PAID;

                    self::saveBooking($data);

                    $data['processed'] = true;

                    wc_update_order_item_meta($item_id, self::AMELIA, $data);
                }
            } catch (ContainerException $e) {
            } catch (\Exception $e) {
            }
        }
    }
}
