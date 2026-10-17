<?php

namespace Modules\Taxido\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use App\Http\Resources\BaseResource;

class SettingsResource extends BaseResource
{
  private const PUBLIC_FIELD_ALLOWLIST = [
    'activation' => [
      'bidding',
      'coupon_enable',
      'allow_negative_balance',
      'driver_subscription',
      'driver_verification',
      'fleet_verification',
      'fleet_vehicle_verification',
      'online_payments',
      'cash_payments',
      'rider_wallet',
      'ride_otp',
      'parcel_otp',
      'driver_tips',
      'referral_enable',
      'force_update',
      'airport_price_enable',
      'surge_price_enable',
      'peak_zone_enable',
      'driver_incentive_enable',
      'additional_minute_charge',
      'additional_distance_charge',
      'additional_weight_charge',
      'sos_enable',
      'full_address_location',
    ],
    'wallet' => [
      'wallet_denominations',
      'tip_denominations',
      'driver_min_wallet_balance',
    ],
    'driver_commission' => [
      'min_withdraw_amount',
      'status',
      'fleet_commission_type',
      'fleet_commission_rate',
      'ambulance_per_km_charge',
      'ambulance_commission_type',
      'ambulance_commission_rate',
      'ambulance_per_minute_rate',
    ],
    'ride' => [
      'ride_request_time_driver',
      'rental_ambulance_request_time',
      'increase_amount_range',
      'weight_unit',
      'find_driver_time_limit',
      'schedule_ride_request_lead_time',
      'driver_max_online_hours',
      'bidding_request_time_limit',
      'min_intracity_radius',
      'max_bidding_fare_driver',
      'parcel_weight_limit',
      'country_code',
      'distance_unit',
      'schedule_min_hour_limit',
      'maximum_seat',
    ],
    'referral' => [
      'minimum_ride_amount',
      'referrer_bonus_percentage',
      'referred_bonus_percentage',
      'referral_amount',
    ],
    'ads' => [
      'native_enable',
      'android_google_ads_id',
      'ios_google_ads_id',
      'native_ios_unit_id',
      'native_android_unit_id',
    ],
  ];

  /**
   * Transform the resource into an array.
   *
   * @return array<string, mixed>
   */
  public function toArray(Request $request): array
  {
    $values = $this['cabbooking_values'];
    return [
      'cabbooking_values' => [
        'general' => [
          'greetings' => $values['general']['greetings'] ?? null,
          'footer_branding_hashtag' => $values['general']['footer_branding_hashtag'] ?? null,
          'footer_branding_attribution' => $values['general']['footer_branding_attribution'] ?? null,
          'ambulance_image' => asset($values['general']['ambulance_image'] ?? null),
          'ambulance_map_icon' => asset($values['general']['ambulance_map_icon'] ?? null)
        ],
        'setting' => [
          'app_version' => $values['setting']['app_version'] ?? null,
          'driver_app_version' => $values['setting']['driver_app_version'] ?? null,
          'splash_screen_url' => $values['setting']['splash_screen_url'] ?? null,
          'driver_splash_screen_url' => $values['setting']['driver_splash_screen_url'] ?? null,
          'rider_privacy_policy' => $values['setting']['rider_privacy_policy'] ?? null,
          'driver_privacy_policy' => $values['setting']['driver_privacy_policy'] ?? null,
        ],
        'location' => [
          'map_provider' => $values['location']['map_provider'] ?? null,
          'radius_per_second' => $values['location']['radius_per_second'] ?? null,
        ],
        'activation' => $this->onlyAllowedFields($values, 'activation'),
        'onboarding' => $values['onboarding'] ?? null,
        'driver_commission' => $this->onlyAllowedFields($values, 'driver_commission'),
        'wallet' => $this->onlyAllowedFields($values, 'wallet'),
        'ride' => $this->onlyAllowedFields($values, 'ride'),
        'referral' => $this->onlyAllowedFields($values, 'referral'),
        'ads' => $this->onlyAllowedFields($values, 'ads'),
      ]
    ];
  }

  private function onlyAllowedFields(array $values, string $section): ?array
  {
    $sectionValues = $values[$section] ?? null;

    return is_array($sectionValues)
      ? Arr::only($sectionValues, self::PUBLIC_FIELD_ALLOWLIST[$section])
      : null;
  }
}
