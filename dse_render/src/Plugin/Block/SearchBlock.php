<?php

namespace Drupal\dse_render\Plugin\Block;

use Drupal\Core\Block\BlockBase;

/**
 * Provides "DSE_render Search Block" block
 * 
 * @Block(
 *   id = "dse_render_search_block",
 *   admin_label = @Translation("DSE_render Search Block"),
 *   category = @Translation("Custom block for DSE")
 * )
 */

 class SearchBlock extends BlockBase {
    public function build() {
        $form = \Drupal::formBuilder() -> getForm('Drupal\dse_render\Form\SearchForm');

        // $form['search_block']['search_with_output']['output']['#attached']

        return $form;
        // return array(
        //     '#theme' => 'autocomplete',
        //     '#search_facets' => $form['facets'],
        //     '#search_form' => $form['search_block']
        // );
    }

    public function getCacheMaxAge() {
        return 0;
    }
 }