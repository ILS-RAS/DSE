<?php

namespace Drupal\dse\Controller;

use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\HttpFoundation\JsonResponse;
use Drupal\Component\Serialization\Json;
use Drupal\node\Entity\Node;

class APIController extends ControllerBase {
  public function getNews($rest_api) {
    $client = \Drupal::httpClient();


    $request = $client->get(
      $rest_api
    );
    $response = $request->getBody()->getContents();
    return $result = json::decode($response);
    echo "<pre>";
    print_r($result);
    exit;    
  }
}
