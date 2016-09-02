<?php
namespace Racknews;

class IPv4Utils {
  public static function getAddresses() {
    return getAllIPv4Allocations();
  }

  public static function getNetworks() {
    return scanRealmByText('ipv4net');
  }
}
