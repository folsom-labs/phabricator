<?php

final class PhabricatorConfigClusterDatabasesController
  extends PhabricatorConfigController {

  public function handleRequest(AphrontRequest $request) {
    $nav = $this->buildSideNavView();
    $nav->selectFilter('cluster/databases/');

    $title = pht('Cluster Databases');

    $crumbs = $this
      ->buildApplicationCrumbs($nav)
      ->addTextCrumb(pht('Cluster Databases'));

    $database_status = $this->buildClusterDatabaseStatus();

    $view = id(new PHUITwoColumnView())
      ->setNavigation($nav)
      ->setMainColumn($database_status);

    return $this->newPage()
      ->setTitle($title)
      ->setCrumbs($crumbs)
      ->appendChild($view);
  }

  private function buildClusterDatabaseStatus() {
    $viewer = $this->getViewer();

    $databases = PhabricatorDatabaseRef::queryAll();
    $connection_map = PhabricatorDatabaseRef::getConnectionStatusMap();
    $replica_map = PhabricatorDatabaseRef::getReplicaStatusMap();
    Javelin::initBehavior('phabricator-tooltips');

    $rows = array();
    foreach ($databases as $database) {
      if ($database->getIsMaster()) {
        $role_icon = id(new PHUIIconView())
          ->setIcon('fa-database sky')
          ->addSigil('has-tooltip')
          ->setMetadata(
            array(
              'tip' => pht('Master'),
            ));
      } else {
        $role_icon = id(new PHUIIconView())
          ->setIcon('fa-download')
          ->addSigil('has-tooltip')
          ->setMetadata(
            array(
              'tip' => pht('Replica'),
            ));
      }

      if ($database->getDisabled()) {
        $conn_icon = 'fa-times';
        $conn_color = 'grey';
        $conn_label = pht('Disabled');
      } else {
        $status = $database->getConnectionStatus();

        $info = idx($connection_map, $status, array());
        $conn_icon = idx($info, 'icon');
        $conn_color = idx($info, 'color');
        $conn_label = idx($info, 'label');

        if ($status === PhabricatorDatabaseRef::STATUS_OKAY) {
          $latency = $database->getConnectionLatency();
          $latency = (int)(1000000 * $latency);
          $conn_label = pht('%s us', new PhutilNumber($latency));
        }
      }

      $connection = array(
        id(new PHUIIconView())->setIcon("{$conn_icon} {$conn_color}"),
        ' ',
        $conn_label,
      );

      if ($database->getDisabled()) {
        $replica_icon = 'fa-times';
        $replica_color = 'grey';
        $replica_label = pht('Disabled');
      } else {
        $status = $database->getReplicaStatus();

        $info = idx($replica_map, $status, array());
        $replica_icon = idx($info, 'icon');
        $replica_color = idx($info, 'color');
        $replica_label = idx($info, 'label');

        if ($database->getIsMaster()) {
          if ($status === PhabricatorDatabaseRef::REPLICATION_OKAY) {
            $replica_icon = 'fa-database';
          }
        } else {
          switch ($status) {
            case PhabricatorDatabaseRef::REPLICATION_OKAY:
            case PhabricatorDatabaseRef::REPLICATION_SLOW:
              $delay = $database->getReplicaDelay();
              if ($delay) {
                $replica_label = pht('%ss Behind', new PhutilNumber($delay));
              } else {
                $replica_label = pht('Up to Date');
              }
              break;
          }
        }
      }

      $replication = array(
        id(new PHUIIconView())->setIcon("{$replica_icon} {$replica_color}"),
        ' ',
        $replica_label,
      );

      $messages = array();

      $conn_message = $database->getConnectionMessage();
      if ($conn_message) {
        $messages[] = $conn_message;
      }

      $replica_message = $database->getReplicaMessage();
      if ($replica_message) {
        $messages[] = $replica_message;
      }

      $messages = phutil_implode_html(phutil_tag('br'), $messages);

      $rows[] = array(
        $role_icon,
        $database->getHost(),
        $database->getPort(),
        $database->getUser(),
        $connection,
        $replication,
        $messages,
      );
    }

    $table = id(new AphrontTableView($rows))
      ->setNoDataString(
        pht('Phabricator is not configured in cluster mode.'))
      ->setHeaders(
        array(
          null,
          pht('Host'),
          pht('Port'),
          pht('User'),
          pht('Connection'),
          pht('Replication'),
          pht('Messages'),
        ))
      ->setColumnClasses(
        array(
          null,
          null,
          null,
          null,
          null,
          null,
          'wide',
        ));

    $doc_href = PhabricatorEnv::getDoclink('Cluster: Databases');

    $header = id(new PHUIHeaderView())
      ->setHeader(pht('Cluster Database Status'))
      ->addActionLink(
        id(new PHUIButtonView())
          ->setIcon('fa-book')
          ->setHref($doc_href)
          ->setTag('a')
          ->setText(pht('Database Clustering Documentation')));

    return id(new PHUIObjectBoxView())
      ->setHeader($header)
      ->setTable($table);
  }

}