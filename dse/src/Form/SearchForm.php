<?php

namespace Drupal\dse\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;

class SearchForm extends FormBase {

    public function getFormId() {
        return 'search_form';
    }

    public function buildForm (array $form, FormStateInterface $form_state) {
        

        $form['search'] = array(
            '#type' => 'textfield',
            '#autocomplete_route_name' => 'dse.autocomplete',
        );

        $form['output'] = array(
            '#type' => 'markup',
            '#markup' => '<h5> По запросу ничего не найдено! </h5>',
            '#theme' => 'search_output',
            '#response_array' => NULL,
            '#nothing_found' => NULL,
            '#prefix' => '<div id="output">',
            '#suffix' => '</div>',
        );


        $form['submit'] = array(
            '#type' => 'button',
            '#value' => 'Submit',
            '#ajax' => array(
                'event' => 'click',
                'callback' => '::showResults',
                'wrapper' => 'output',
                'method' => 'replaceWith'
            )
        );

        return $form;
    }


    public function submitForm(array &$form, FormStateInterface $form_state) {
    }

    public function showResults(array &$form, FormStateInterface $form_state) {
        $value = $form_state -> getValue('search');

        $config = $this -> config('dse_api.settings') -> get('url_list');
        $sources = [];
        foreach ($config as $url) {
            if ($url['enabled'] = true) {
                $sources[] = $url['full_name'];
            }
        }
        $conn = \Drupal::database();
        $response_array = [];

        foreach ($sources as $source) {

            $response = $conn 
            -> select('dse_vocables', 'v') 
            -> fields('v', ['full_name', 'title', 'format_title', 'url'])
            -> condition('v.full_name', $source, '=')
            -> condition('v.title', $value . '%', 'LIKE')
            -> orderBy('v.format_title', 'ASC')
            -> execute()
            -> fetchAll();

            if ($response) { 
                $response_array[$source] = $response;
            }

        }

        if ($response_array) {
            $form['output']['#response_array'] = $response_array;
        } else {
            $form['output']['#nothing_found'] = true;
        }

        return $form['output'];

    }

}