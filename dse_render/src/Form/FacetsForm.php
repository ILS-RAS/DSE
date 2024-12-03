<?php

namespace Drupal\dse_render\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;

class FacetsForm extends FormBase {

    private function getWordCount(): int {
        $count = 0;

        $session = \Drupal::request() -> getSession();
        $active_list = $session -> get('dse_render.active_list');

        $conn = $conn = \Drupal::database();
        foreach (array_keys($active_list, 1) as $_id) {
            $local_count = $conn -> select('dse_render_vocables', 'v')
            -> condition('v.source_id', $_id) 
            -> countQuery()
            -> execute()
            -> fetchField();

            $count += $local_count;
        }

        return $count;
    }

    public function getFormId() {
        return 'dse_render_facets_form';
    }
    public function buildForm(array $form, FormStateInterface $form_state) {
        $session = \Drupal::request() -> getSession();
        $active_list = $session -> get('dse_render.active_list');

        $conn = \Drupal::database();
        $query = $conn -> select('dse_render_datasources', 'd') -> condition('d.active', 1) -> fields('d', ['full_name', '_id', 'update_view_url']);
        $result = $query -> execute() -> fetchAll();

        if (!($session -> get('dse_render.active_list')) || count($result) != count($active_list)) {
            $new_list = [];

            foreach ($result as $record) {
                $new_list[$record -> _id] = 1;
            }
            $session -> set('dse_render.active_list', $new_list);
            $active_list = $session -> get('dse_render.active_list');
        }

        $form['facets'] = array(
            '#type' => 'container',
            '#prefix' => '<div id=facets>',
            '#suffix' => '</div>'
        );


        $form['facets']['datasources'] = array(
            '#type' => 'fieldset',
            '#tree' => TRUE,
            '#prefix' => '<div class="card-body">',
            '#suffix' => '</div>'
        );
        
        
        foreach ($result as $record) {
            $_id = $record -> _id;
            $url = explode('/api', $record -> update_view_url)[0];

            $voc_count = $conn -> select('dse_render_vocables', 'd')
            -> condition('d.source_id', $_id)
            -> countQuery()
            -> execute()
            -> fetchField();

            $form['facets']['datasources'][$_id] = array(
                '#type' => 'container',
                '#tree' => TRUE,
                '#attributes' => [
                    'class' => [
                        'd-inline-flex',
                        'justify-content-between',
                        'align-items-center',
                        'column-margin'
                    ]
                ]
            );

            $form['facets']['datasources'][$_id]['name'] = array(
                '#type' => 'item',
                '#theme' => 'search_icon',
                '#datasource_url' => $url,
                '#datasource_name' => $record -> full_name,
                '#voc_count' => $voc_count
            );
        
            $form['facets']['datasources'][$_id]['enabled'] = array(
                '#type' => 'checkbox',
                '#default_value' => $active_list[$_id],
                '#ajax' => [
                    'callback' => [$this, 'setDatasources'],
                    'event' => 'change',
                    'wrapper' => 'facets',
                    'progress' => [
                        'type' => 'none'
                    ],
                    'disable-refocus' => TRUE,
                ]
            );
          }

          $count = $this -> getWordCount();
          $form['facets']['footer'] = array(
            '#type' => 'item',
            '#title' => 'Всего доступных вокабул: ' . $count,
            '#prefix' =>  '<div class="card-footer text-secondary">',
            '#suffix' => '</div>'
          );

        return $form;
    }

    public function validateForm(array &$form, FormStateInterface $form_state) {

    }

    public function submitForm(array &$form, FormStateInterface $form_state) {
    }

    public function setDatasources(array &$form, FormStateInterface $form_state) {
        $session = \Drupal::request() -> getSession();
        $conn = \Drupal::database();

        $triggering_elt = $form_state -> getTriggeringElement();
        $trigger_id = $triggering_elt['#array_parents'][2];

        $enabled = $form_state -> getValues()['datasources'][$trigger_id]['enabled'];
        $active_list = $session -> get('dse_render.active_list'); 

        if (!$enabled) {
            $active_list[$trigger_id] = 0;
        } else {
            $active_list[$trigger_id] = 1;
        }

        $session -> set('dse_render.active_list', $active_list);

        $count = $this -> getWordCount();
        $form['facets']['footer']['#title'] = 'Всего доступных вокабул: ' . $count;  
        
        return $form['facets'];
    }
}