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
            '#type' => 'container',
            '#prefix' => '<div id="facets" class="card mt-3 mb-3">',
            '#suffix' => '</div>',
        );

        $form['facets']['title'] = array(
            '#type' => 'item',
            '#title' => $this -> t('Доступные источники'),
            '#prefix' => '<div class="card-header text-center fw-bold">',
            '#suffix' => '</div>',
        );


        $form['facets']['datasources'] = array(
            '#type' => 'table',
            '#tree' => TRUE,
            '#prefix' => '<div class="card-body">',
            '#suffix' => '</div>',
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

        $enabled = $form_state -> getValues()['datasources'][$_id]['enabled'];
        $active_list = $session -> get('dse_render.active_list'); 

        if (!$enabled) {
            $active_list[$_id] = 0;
        } else {
            $active_list[$_id] = 1;
        }

        $session -> set('dse_render.active_list', $active_list);
        
        return $form['facets'];
    }
}