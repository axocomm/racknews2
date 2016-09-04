<?php
namespace Racknews;

class IPv4Address {
  public static function all() {
    return getAllIPv4Allocations();
  }

  public static function getNetworks() {
    return scanRealmByText('ipv4net');
  }

  public static function ipToInt($ip_str) {
    $octets = array_map(function ($oc) {
      return (int) $oc;
    }, explode('.', $ip_str));

    return ($octets[0] * pow(256, 3)) +
           ($octets[1] * pow(256, 2)) +
           ($octets[2] * pow(256, 1)) +
           ($octets[3] * pow(256, 0));
  }

  public static function ipToBin($ip_str) {
    $ip_int = self::ipToInt($ip_str);
    return ip4_int2bin($ip_int);
  }
}
