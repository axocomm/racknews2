<?php
namespace Racknews;

class IPv4Utils {
  public static function getAddresses($just_get = false) {
    $networks = self::getNetworks();
    return array_reduce(
      $networks,
      function ($acc, $network) {
        $info = spotEntity('ipv4net', $network['id']);
        loadIPv4AddrList($info);

        $network['addresses'] = array_map(
          function ($addr) {
            return 1;
            return array(
              'object-name' => $addr['name'],
              'address'     => $addr['ip']
            );
          },
          $info['addrlist']
        );

        $acc[] = $network;
        return $acc;
      },
      array()
    );
  }

  public static function getNetworks() {
    return scanRealmByText('ipv4net');
  }
}
