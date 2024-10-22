<?php

namespace Drupal\dse\Plugin\Block;

use Drupal\Core\Block\BlockBase;

/**
 * Provides "DSE Affixoid News Block" block
 * 
 * @Block(
 *   id = "dse_search_block",
 *   admin_label = @Translation("DSE Search Block"),
 *   category = @Translation("Custom block for DSE")
 * )
 */


class SearchBlock extends BlockBase {

    public function build() {
        $form = \Drupal::formBuilder() -> getForm('Drupal\dse\Form\SearchForm');

        return array(
            '#theme' => 'autocomplete',
            '#search_form' => $form,
        );
    }
}