<?php

final class PhabricatorFileTemporaryGarbageCollector
  extends PhabricatorGarbageCollector {

  const COLLECTORCONST = 'files.ttl';

  public function getCollectorName() {
    return pht('Files (TTL)');
  }

  public function hasAutomaticPolicy() {
    return true;
  }

  public function collectGarbage() {
    $files = id(new PhabricatorFile())->loadAllWhere(
      'ttl < %d LIMIT 100',
      time());

    foreach ($files as $file) {
      $file->delete();
    }

    return (count($files) == 100);
  }

}
