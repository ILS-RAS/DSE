<?php

namespace Drupal\dse\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\dse\Controller\APIController;
/**
 * Provides "DSE Affixoid News Block" block
 * 
 * @Block(
 *   id = "dse_affixoid_news_block",
 *   admin_label = @Translation("DSE Affixoid News Block"),
 *   category = @Translation("Custom block for DSE")
 * )
 */

 class AffixoidNewsBlock extends BlockBase {
    /**
     * {@inheritdoc}
     */
    public function build() {

      $apiController = new APIController;

      $news =  $apiController->getNews('https://affixoid.iling.spb.ru/api/v1/news');

      $arr = array();

      foreach ($news as &$value) {
        array_push($arr, [
          '#prefix' => '<span class="badge bg-light">',
          '#type' => 'markup',
          '#markup' => $value['title'],
          '#suffix' => '</span>'
     ]);
      }
      
      return $arr;
    }

 }
