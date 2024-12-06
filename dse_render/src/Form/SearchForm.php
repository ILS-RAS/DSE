<?php

namespace Drupal\dse_render\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;

class SearchForm extends FormBase {

    public function getFormId() {
        return 'dse_render_search_form';
    }

    public function buildForm(array $form, FormStateInterface $form_state) {
        $form['#attached']['library'][] = 'dse_render/search_block';

        $form['search_block'] = array(
            '#type' => 'container',
            '#attributes' => [
                'class' => [
                    'dse_main_container'
                ]
            ]
        );

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
            $_ids = array_keys($active_list);

            $conn = \Drupal::database();

            foreach($_ids as $datasource_id) {
                $name_query = $conn -> select('dse_render_datasources', 'd')
                -> fields('d', ['full_name', 'style', 'ajax_view_url'])
                -> condition('d._id', $datasource_id);
                $name_result = $name_query -> execute() -> fetchAll();
                $datasource_name = $name_result[0] -> full_name;
                $datasource_ajax = $name_result[0] -> ajax_view_url;
                $datasource_style = $name_result[0] -> style;
                
                $js_datasources[$datasource_id] = ['name' => $datasource_name, 'base_url' => explode('/api', $datasource_ajax)[0], 'style' => $datasource_style, 'ajax_url' => $datasource_ajax];

                if ($active_list[$datasource_id] = 1) {                  
                    $voc_query = $conn -> select('dse_render_vocables', 'v')
                    -> fields('v', ['search_title', 'display_title', 'node', '_id'])
                    -> condition('v.source_id', $datasource_id)
                    -> condition('v.search_title', $value)
                    -> orderBy('v.display_title', 'ASC');

                    $voc_result = $voc_query -> execute() -> fetchAll();
                    
                    if ($voc_result) {
                        $response_array[$datasource_id] = ['source' => $datasource_name, 'style' => $datasource_style, 'responses' => $voc_result];
                        foreach ($voc_result as $res) {
                            $js_results[$res -> _id] = ['source' => $datasource_id, 'node' => $res -> node];                           
                        }
                    }
                }  
            }

            if ($response_array) {
                $form['search_block']['search_with_output']['output']['#response_array'] = $response_array;
                $form['search_block']['search_with_output']['output']['#attached']['drupalSettings']['dse_render']['js_results'] = json_encode($js_results);
                $form['search_block']['search_with_output']['output']['#attached']['drupalSettings']['dse_render']['js_datasources'] = json_encode($js_datasources);
            } else {
                $form['search_block']['search_with_output']['output']['#nothing_found'] = true;
                $form['search_block']['search_with_output']['output']['#attached']['drupalSettings']['dse_render']['js_results'] = null;
                $form['search_block']['search_with_output']['output']['#attached']['drupalSettings']['dse_render']['js_datasources'] = null;
            }
        }
        return $form['search_block']['search_with_output']['output'];
    }
}