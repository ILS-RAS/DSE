<?php

namespace Drupal\dse_render\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;

class FacetsForm extends FormBase {

    public function getFormId() {
        return 'dse_render_facets_form';
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

        $form['facets'] = array(
            '#type' => 'details',
            '#title' => $this -> t('Доступные источники'),
            '#open' => TRUE,
            '#tree' => TRUE,
            '#prefix' => '<div id="facets" class="me-3">',
            '#suffix' => '</div>'
        );


        $form['facets']['datasources'] = array(
            '#type' => 'table',
            '#tree' => TRUE,
        );
        
        
        foreach ($result as $record) {
            $_id = $record -> _id;

            $form['facets']['datasources'][$_id]['name'] = array(
                '#type' => 'item',
                '#title' => $record -> full_name
            );
        
            $form['facets']['datasources'][$_id]['enabled'] = array(
                '#type' => 'checkbox',
                '#default_value' => $active_list[$_id],
                '#ajax' => [
                    'callback' => [$this, 'setDatasources'],
                    'event' => 'change',
                    'wrapper' => 'facets',
                ]
            );
          }

        return $form;
    }

    public function validateForm(array &$form, FormStateInterface $form_state) {

    }

    public function submitForm(array &$form, FormStateInterface $form_state) {
    }

    public function setDatasources(array &$form, FormStateInterface $form_state) {
     
        $session = \Drupal::request() -> getSession();

        $triggering_elt = $form_state -> getTriggeringElement();
        $_id = $triggering_elt['#array_parents'][2];

        $enabled = $form_state -> getValues()['facets']['datasources'][$_id]['enabled'];

        $active_list = $session -> get('dse_render.active_list'); 

        if (!$enabled) {
            $active_list[$_id] = 0;
        } else {
            $active_list[$_id] = 1;
        }

        $session -> remove('dse_render.active_list');
        $session -> set('dse_render.active_list', $active_list);
        
        return $form['facets'];
    }
}