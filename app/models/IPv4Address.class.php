<?php
namespace Racknews;

/**
 * A Racknews IPv4 address.
 */
class IPv4Address {

  /**
   * Get all IPv4 allocations from RackTables.
   *
   * @return array
   */
  public static function all() {
    return getAllIPv4Allocations();
  }

  /**
   * Get IPv4 networks.
   *
   * @return array
   */
  public static function getNetworks() {
    return scanRealmByText('ipv4net');
  }

  /**
   * Convert an IP address to its corresponding integer.
   *
   * @param string $ip_str the IP address
   *
   * @return int its integer representation
   */
  public static function ipToInt($ip_str) {
    $octets = array_map(function ($oc) {
      return (int) $oc;
    }, explode('.', $ip_str));

    return ($octets[0] * pow(256, 3)) +
           ($octets[1] * pow(256, 2)) +
           ($octets[2] * pow(256, 1)) +
           ($octets[3] * pow(256, 0));
  }

  /**
   * Convert an IP address to its corresponding binary value.
   *
   * @param string $ip_str the IP address
   *
   * @return string its packed binary representation
   */
  public static function ipToBin($ip_str) {
    $ip_int = self::ipToInt($ip_str);
    return ip4_int2bin($ip_int);
  }
}
