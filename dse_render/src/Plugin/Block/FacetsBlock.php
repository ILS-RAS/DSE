<?php

namespace Drupal\dse_render\Plugin\Block;

use Drupal\Core\Block\BlockBase;

/**
 * Provides "DSE_render Search Block" block
 * 
 * @Block(
 *   id = "dse_render_facets_block",
 *   admin_label = @Translation("DSE_render Facets Block"),
 *   category = @Translation("Custom block for DSE")
 * )
 */

 class FacetsBlock extends BlockBase {
    public function build() {
        $form = \Drupal::formBuilder() -> getForm('Drupal\dse_render\Form\FacetsForm');

        // $conn = \Drupal::database();
        // $count = $conn -> select('dse_render_vocables', 'd') -> countQuery() -> execute() -> fetchField();

        return array(
            '#theme' => 'search_facets',
            '#facets' => $form,
            // '#overall_count' => $count,
        );
    }

    public function getCacheMaxAge() {
        return 0;
    }
 }