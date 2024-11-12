<?php

namespace Drupal\dse_render\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;

class SearchForm extends FormBase {

    public function getFormId() {
        return 'dse_render_search_form';
    }

    public function buildForm(array $form, FormStateInterface $form_state) {
        $session = \Drupal::request() -> getSession();
        $active_list = $session -> get('dse_render.active_list');

        $conn = \Drupal::database();
        $query = $conn -> select('dse_render_datasources', 'd') -> condition('d.active', 1) -> fields('d', ['full_name', '_id']);
        $result = $query -> execute() -> fetchAll();
        
        if (!($session -> get('dse_render.active_list')) || count($result) != count($active_list)) {
            $new_list = [];

            foreach ($result as $record) {
                $new_list[$record -> _id] = 1;
            }
            $session -> set('dse_render.active_list', $new_list);
            $active_list = $session -> get('dse_render.active_list');
        }

        $form['#attached']['library'][] = 'dse_render/search_block';

        $form['search_block'] = array(
            '#type' => 'container',
            '#attributes' => [
                'class' => [
                    'dse_main_container'
                ]
            ]
        );

        $form['search_block']['facets'] = array(
            '#type' => 'details',
            '#title' => $this -> t('Доступные источники'),
            '#open' => TRUE,
            '#tree' => TRUE,
            '#prefix' => '<div class="me-3">',
            '#suffix' => '</div>'
        );


        $form['search_block']['facets']['datasources'] = array(
            '#type' => 'table',
            '#tree' => TRUE,
        );
        
        
        foreach ($result as $record) {
            $_id = $record -> _id;

            $form['search_block']['facets']['datasources'][$_id]['name'] = array(
                '#type' => 'item',
                '#title' => $record -> full_name
            );
        
            $form['search_block']['facets']['datasources'][$_id]['enabled'] = array(
                '#type' => 'checkbox',
                '#default_value' => $active_list[$_id],
                '#ajax' => [
                    'callback' => [$this, 'setDatasources'],
                    'event' => 'change',
                    'wrapper' => 'edit-output',
                ]
            );
          }

        $form['search_block']['search_with_output'] = array(
            '#type' => 'container',
            '#attributes' => [
                'class' => [
                    'w-100',
                ]
            ]
        );

        $form['search_block']['search_with_output']['search_bar'] = array(
            '#type' => 'container',
            '#attributes' => [
                'class' => [
                    'd-inline-flex',
                    'w-100',
                    'flex-row',
                    'align-items-center'
                ]
            ]
        );

        $form['search_block']['search_with_output']['search_bar']['search'] = array(
            '#type' => 'textfield',
            '#autocomplete_route_name' => 'dse_render.autocomplete'
        );

        $form['search_block']['search_with_output']['search_bar']['submit'] = array(
            '#type' => 'button',
            '#value' => 'Поиск',
            '#ajax' => [
                'event' => 'click',
                'callback' => '::showResults',
                'wrapper' => 'output',
            ]
        );

        $form['search_block']['search_with_output']['output'] = array(
            '#type' => 'markup',
            '#markup' => '',
            '#theme' => 'search_output',
            '#response_array' => NULL,
            '#nothing_found' => NULL,
            '#prefix' => '<div id="output" class="mb-3">',
            '#suffix' => '</div>',
            '#attached' => [
                'library' => 'dse_render/ajax_results'
            ]

        );

        return $form;
    }

    public function validateForm(array &$form, FormStateInterface $form_state) {

    }

    public function submitForm(array &$form, FormStateInterface $form_state) {
        $this -> messenger() -> addMessage('you have clicked the button');
    }

    public function showResults(array &$form, FormStateInterface $form_state) {
        $response_array = [];
        $value = $form_state -> getValue('search');

        if ($value) {
            $session = \Drupal::request() -> getSession();
            $active_list = $session -> get('dse_render.active_list'); 
            $_ids = array_keys($active_list, 1);

            $conn = \Drupal::database();

            foreach($_ids as $_id) {
                $name_query = $conn -> select('dse_render_datasources', 'd')
                -> fields('d', ['full_name', 'style', 'ajax_view_url'])
                -> condition('d._id', $_id);

                $name_result = $name_query -> execute() -> fetchAll();
                $datasource_name = $name_result[0] -> full_name;
                $datasource_style = $name_result[0] -> style;
                $datasource_ajax = $name_result[0] -> ajax_view_url;

                $voc_query = $conn -> select('dse_render_vocables', 'v')
                -> fields('v', ['search_title', 'display_title', 'node', '_id'])
                -> condition('v.source_id', $_id)
                -> condition('v.search_title', $value)
                -> orderBy('v.display_title', 'ASC');

                $voc_result = $voc_query -> execute() -> fetchAll();
                
                if ($voc_result) {
                    $response_array[$_id] = ['source' => $datasource_name, 'style' => $datasource_style, 'responses' => $voc_result];
                    foreach ($voc_result as $res) {
                        $js_array[$res -> display_title] = ['source' => $datasource_name, 'style' => $datasource_style, 'ajax_url' => $datasource_ajax, 'node' => $res -> node,
                            '_id' => $res -> _id];
                    }
                }  
            }

            if ($response_array) {
                $form['search_block']['search_with_output']['output']['#response_array'] = $response_array;
                $form['search_block']['search_with_output']['output']['#attached']['drupalSettings']['dse_render']['js_array'] = json_encode($js_array);
            } else {
                $form['search_block']['search_with_output']['output']['#nothing_found'] = true;
                $form['search_block']['search_with_output']['output']['#attached']['drupalSettings']['dse_render']['js_array'] = null;
            }
        }
        return $form['search_block']['search_with_output']['output'];
    }

    public function setDatasources(array &$form, FormStateInterface $form_state) {
     
        $session = \Drupal::request() -> getSession();

        $triggering_elt = $form_state -> getTriggeringElement();
        $_id = $triggering_elt['#array_parents'][3];

        $enabled = $form_state -> getValues()['facets']['datasources'][$_id]['enabled'];

        $active_list = $session -> get('dse_render.active_list'); 

        if (!$enabled) {
            $active_list[$_id] = 0;
        } else {
            $active_list[$_id] = 1;
        }

        $session -> remove('dse_render.active_list');
        $session -> set('dse_render.active_list', $active_list);
        
        return $form['search_block'];
    }

}