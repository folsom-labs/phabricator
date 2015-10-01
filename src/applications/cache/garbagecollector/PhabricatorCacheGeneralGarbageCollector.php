<?php

final class PhabricatorCacheGeneralGarbageCollector
  extends PhabricatorGarbageCollector {

  const COLLECTORCONST = 'cache.general';

  public function getCollectorName() {
    return pht('General Cache');
  }

  public function getDefaultRetentionPolicy() {
    return phutil_units('30 days in seconds');
  }

  public function collectGarbage() {
    $key = 'gcdaemon.ttl.general-cache';
    $ttl = PhabricatorEnv::getEnvConfig($key);
    if ($ttl <= 0) {
      return false;
    }

    $cache = new PhabricatorKeyValueDatabaseCache();
    $conn_w = $cache->establishConnection('w');

    queryfx(
      $conn_w,
      'DELETE FROM %T WHERE cacheCreated < %d
        ORDER BY cacheCreated ASC LIMIT 100',
      $cache->getTableName(),
      time() - $ttl);

    return ($conn_w->getAffectedRows() == 100);
  }

}
