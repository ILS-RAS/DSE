<?php

namespace Drupal\dse_render\Form;

use Drupal\Core\Url;
use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\MessageCommand;
use Drupal\Core\Ajax\RedirectCommand;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Database\Database;
use Drupal\Component\Serialization\Json;

class SettingsForm extends ConfigFormBase {
    
    public function getFormId() {
        return 'dse_render_settings';
    }

    protected function getEditableConfigNames() {
        return [
            'dse_render.styles'
        ];
    }

    public function buildForm(array $form, FormStateInterface $form_state) {

        $form['add_source'] = array(
            '#type' => 'details',
            '#title' => $this -> t('Добавить источник'),
            '#open' => TRUE,
            '#tree' => TRUE,
        );

        $form['add_source']['source_name'] = array(
            '#type' => 'textfield',
            '#title' => $this -> t('Название источника'),
            '#max_length' => 255,
            '#required' => TRUE,
        );

        $form['add_source']['source_update_view'] = array(
            '#type' => 'url',
            '#title' => $this -> t('Ссылка на представление для вокабул'),
            '#required' => TRUE,
        );

        $form['add_source']['source_ajax_view'] = array(
            '#type' => 'url',
            '#title' => $this -> t('Ссылка на представление для AJAX-запроса'),
            '#required' => TRUE,
        );

        $styles= $this -> config('dse_render.styles') -> get('styles');
        $options = array_combine($styles, $styles);

        $form['add_source']['source_style'] = array(
            '#type' => 'select',
            '#title' => $this -> t('Стиль отображения данных с источника'),
            '#options' => $options,
            '#required' => TRUE,
        );

        $form['add_source']['save_source'] = array(
            '#type' => 'submit',
            '#value' => $this -> t('Сохранить источник'),
            '#submit' => ['::addSource'],
        );

        $form['datasources'] = array(
            '#type' => 'table',
            '#tree' => TRUE,
            '#header' => [
                $this -> t('Имя источника'),
                $this -> t('Представление вокабул'),
                $this -> t('Представление AJAX'),
                $this -> t('Стиль отображения'),
                $this -> t('Доступен поиск'),
                [
                    'data' => $this -> t('Действия'),
                    'colspan' => 2,
                ]
            ]
        );

        $conn = \Drupal::database();
        $query = $conn -> select('dse_render_datasources', 'v') -> fields('v');
        $result = $query -> execute() -> fetchAll();
        foreach ($result as $record) {
            $_id = $record -> _id;

            $form['datasources'][$_id]['full_name'] = array(
                '#type' => 'item',
                '#title' => $this -> t($record -> full_name)
            );

            $form['datasources'][$_id]['update_view'] = array(
                '#type' => 'item',
                '#title' => $this -> t($record -> update_view_url)
            );

            $form['datasources'][$_id]['ajax_view'] = array(
                '#type' => 'item',
                '#title' => $this -> t($record -> ajax_view_url)
            );

            $form['datasources'][$_id]['style'] = array(
                '#type' => 'item',
                '#title' => $this -> t($record -> style)
            );

            $active = $record -> active;
            $form['datasources'][$_id]['active'] = array(
                '#type' => 'checkbox',
                '#default_value' => (bool) $active
            );

            $form['datasources'][$_id]['initialize'] = array(
                '#type' => 'button',
                '#value' => 'Инициализировать',
                '#limit_validation_errors' => [],
                '#ajax' => [
                    'event' => 'click',
                    'callback' => '::initializeSource',
                    'progress' => [
                        'type' => 'throbber',
                        'message' => $this -> t('Собираем данные...')
                    ]
                ]
            );

            $form['datasources'][$record -> _id]['delete'] = array(
                '#type' => 'submit',
                '#value' => 'Удалить ресурс',
                '#limit_validation_errors' => [],
                '#submit' => ['::deleteSource']
            );
        }
        $form['save_config'] = array(
            '#type' => 'submit',
            '#value' => 'Сохранить изменения',
            '#limit_validation_errors' => [['datasources']],
            '#submit' => ['::saveConfiguration']
        );

        $form['clear_all'] = array(
            '#type' => 'submit',
            '#value' => 'Очистить данные',
            '#limit_validation_errors' => [],
            '#submit' => ['::clearData']
        );
        
        return parent::buildForm($form, $form_state);
    }

    public function addSource(array &$form, FormStateInterface $form_state) {
        $conn = \Drupal::database();
        $values = $form_state -> getValues()['add_source'];
        $url_string = str_contains($values['source_update_view'], '?') ? $values['source_update_view']. '&' : $values['source_update_view'] . '?';

        $query = $conn -> insert('dse_render_datasources')
        -> fields([
            '_id' => uniqid(),
            'full_name' => $values['source_name'],
            'update_view_url' => $url_string,
            'ajax_view_url' => $values['source_ajax_view'],
            'style' => $values['source_style'],
            'initialized' => 0,
            'active' => 0
        ]) -> execute();


        $this -> messenger() -> addMessage('Источник сохранён');
    }

    public function clearData(array &$form, FormStateInterface $form_state) {
        #TO DO : Add confirmation message
        $conn = \Drupal::database();

        $query = $conn -> delete('dse_render_datasources') -> execute();
        
        $this -> messenger() -> addMessage('Данные очищены');
    }
    public function deleteSource(array &$form, FormStateInterface $form_state) {
        #TO DO : Add confirmation message
        $triggering_elt = $form_state -> getTriggeringElement();
        $_id = $triggering_elt['#array_parents'][1];
        
        $conn = \Drupal::database();
        $query = $conn -> delete('dse_render_datasources')
        -> condition('_id', $_id)
        -> execute();

        $this -> messenger() -> addMessage('Источник удалён');
    }

    public function initializeSource(array &$form, FormStateInterface $form_state) {
        $ajax_response = new AjaxResponse();

        $conn = \Drupal::database();
        $client = \Drupal::httpClient();

        $triggering_elt = $form_state -> getTriggeringElement();
        $_id = $triggering_elt['#array_parents'][1];
        
        $query = $conn -> select('dse_render_datasources', 'd') -> condition('_id', $_id) -> fields('d', ['update_view_url']); 
        $result = $query -> execute() -> fetchField();

        $page = 0;
        while (True) {
            try {
                $request = $client -> get($result . "page=" . $page);
                $response = json::decode($request->getBody()->getContents());
                if (count($response) == 0) {
                  break;
                }
                $page += 1;
              } catch (Exception $e) {
                break;
              } 
    
            try {
              foreach ($response as &$vocable) {
                $query = $conn -> insert('dse_render_vocables') 
                -> fields([
                  '_id' => uniqid(),
                  'source_id' => $_id,
                  'search_title' => str_replace('́', '', $vocable['title']),
                  'display_title' => $vocable['format_title'],
                  'node' => $vocable['view_node'],
                  'created_time' => explode('+', $vocable['created'])[0]
                ])
                -> execute();
              }
            } catch(Exception $e) {
                $ajax_response -> addCommand(new MessageCommand('Что-то пошло не так. Попытайтесь очистить базу данных и повторите попытку.', NULL, [
                    'type' => 'warning',
                ]));
                return $ajax_response;
            }
        }

        $query = $conn -> update('dse_render_datasources') 
        -> fields([
            'initialized' => 1,
        ]) 
        -> condition('_id', $_id) 
        -> execute();

        $currentURL = Url::fromRoute('<current>');
        $ajax_response -> addCommand(new RedirectCommand($currentURL -> toString()));
        return $ajax_response;
    }

    public function saveConfiguration(array &$form, FormStateInterface $form_state) {
        $conn = \Drupal::database();
        $query = $conn -> select('dse_render_datasources', 'd') -> fields('d', ['_id']);
        $result = $query -> execute() -> fetchAll();

        foreach($result as $source) {
            $_id = $source -> _id;

            $enabled = $form_state -> getValues()['datasources'][$_id]['active'];

            
            $query = $conn -> update('dse_render_datasources')
            -> fields([
                'active' => $enabled
            ])
            -> condition('_id', $_id)
            -> execute();
        }

        $this -> messenger() -> addMessage('Изменения сохранены! Не забудьте очистить кэш!');
    }
}