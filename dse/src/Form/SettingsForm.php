<?php 

namespace Drupal\dse\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Database\Database;
use Drupal\Component\Serialization\Json;
use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\ReplaceCommand;
use Drupal\Core\Ajax\OpenModalDialogCommand;
use Exception;


class SettingsForm extends ConfigFormBase {

    /**
     * {@inheritdoc}
     */
      public function getFormId() {
        return 'dse_api_settings';
      }
     
    /**
     * {@inheritdoc}
     */
      protected function getEditableConfigNames() {
        return [
          'dse_api.settings',
        ];
      }
     
    /**
     * {@inheritdoc}
     */
      public function buildForm(array $form, FormStateInterface $form_state) {
        $config = $this->config('dse_api.settings');
        $urls = $config->get('url_list');
     
        $form['add_link'] = array(
          '#type' => 'details',
          '#tree' => TRUE,
          '#title' => $this->t('Добавить ссылку на ресурс'),
          '#collapsible' => TRUE,
          '#collapsed' => TRUE
        );

        $form['add_link']['link'] = array(
          '#type' => 'textfield',
          '#title' => $this->t('Ссылка на данные ресурса'),
          '#required' => TRUE,
        );

        $form['add_link']['full_name'] = array(
          '#type' => 'textfield',
          '#title' => $this -> t('Полное название ресурса'),
          '#required' => TRUE,
        );

        $form['add_link']['submit_link'] = array(
          '#type' => 'submit',
          '#value' => 'Добавить ссылку',
          '#submit' => ['::addLink']
        );

        $form['config_urls'] = array(
          '#type' => 'table',
          '#tree' => TRUE,
          '#header' => array(
            $this -> t('URL'),
            $this -> t('Активно'),
            [
              'data' => $this -> t('Действия'),
              'colspan' => 2,
            ]
          )
        );

        if ($urls) {
        for ($i = 0; $i < count($urls); $i++) {

          $form['config_urls'][$i]['full_name'] = array(
            '#type' => 'item',
            '#title' => $urls[$i]['full_name']
          );

          $form['config_urls'][$i]['enable'] = array(
            '#type' => 'checkbox',
            '#default_value' => $urls[$i]['enabled']
          );

          $form['config_urls'][$i]['synchronize'] = array(
            '#type' => 'submit',
            '#value' => $this -> t('Синхронизировать'),
            '#name' => 'synchronization_'. $i,
            '#submit' => ['::synchronizeDB'],
            '#limit_validation_errors' => [],
          );

          $form['config_urls'][$i]['delete'] = array(
            '#type' => 'submit',
            '#value' => 'Удалить ресурс',
            '#name' => 'delete_' . $i,
            '#submit' => ['::deleteLink'],
            '#limit_validation_errors' => [],
          );
        };
      }

      $form['save_config'] = array(
        '#type' => 'submit',
        '#value' => 'Сохранить настройки',
        '#submit' => ['::saveConfig'],
        '#limit_validation_errors' => [['config_urls']],
      );

      $form['reset_config'] = array(
        '#type' => 'submit',
        '#value' => 'Удалить все данные',
        '#limit_validation_errors' => [],
        '#submit' => ['::resetConfig']
      );

        return parent::buildForm($form, $form_state);
      }

      public function resetConfig(array &$form, FormStateInterface $form_state) {
        $this -> configFactory -> getEditable('dse_api.settings') 
        -> delete();

        $clear_db = \Drupal::database() -> truncate('dse_vocables') -> execute();

        parent::submitForm($form, $form_state);
      }

      public function addLink(array &$form, FormStateInterface $form_state) {
        $config = $this->config('dse_api.settings');
        $urls = $config->get('url_list');

        $urls[] = ['url_string' => $form_state -> getValues()['add_link']['link'],
        'enabled'=> false, 
        'full_name' => $form_state -> getValues()['add_link']['full_name'],
        'changed_time' => 0];

         $this->configFactory->getEditable('dse_api.settings')
        ->set('url_list', $urls) ->save();

        parent::submitForm($form, $form_state);
      }

      public function synchronizeDB(array &$form, FormStateInterface $form_state) {
        $index = explode('_', $form_state -> getTriggeringElement()['#name'])[1];
        $url = $this -> config('dse_api.settings') -> get('url_list')[$index];

        $conn = \Drupal::database();

        $client = \Drupal::httpClient();
        $request = $client -> get($url['url_string']);
        $response = json::decode($request->getBody()->getContents());


        try {
          foreach ($response as &$vocable) {
            $query = $conn -> insert('dse_vocables') 
            -> fields([
              'title' => $vocable['title'],
              'full_name' => $url['full_name'],
              'url' => $vocable['view_node'],
              'changed_time' => $vocable['changed'],
              'format_title' => $vocable['format_title']
            ])
            -> execute();
          }
          $this->messenger()->addMessage('Данные синхронизированы!');
        } catch(Exception $e) {
          $this->messenger()->addMessage('Что-то пошло не так. Попытайтесь очистить базу данных и повторите попытку.');
        }

        
        parent::submitForm($form, $form_state);

      }
      public function saveConfig(array &$form, FormStateInterface $form_state) {
        $config = $this -> config('dse_api.settings');
        $urls = $config -> get('url_list');

        for ($i = 0; $i < count($urls); $i++) {
          $enabled = $form_state -> getValues()['config_urls'][$i]['enable'];
          $urls[$i]['enabled'] = $enabled;
        }
        
        $this -> configFactory -> getEditable('dse_api.settings') -> set('url_list', $urls) -> save();

        parent::submitForm($form, $form_state);
        
      }

      public function deleteLink(array &$form, FormStateInterface $form_state) {
        $urls = $this -> config('dse_api.settings') -> get('url_list');
        $index = explode('_', $form_state -> getTriggeringElement()['#name'])[1];

        $alias = $urls[$index]['full_name'];

        $conn = \Drupal::database();
        $query = $conn -> delete('dse_vocables') -> condition('full_name', $alias) -> execute();

        unset($urls[$index]);
        $urls = array_values($urls);

        $this -> configFactory -> getEditable('dse_api.settings') -> set('url_list', $urls) -> save();

        parent::submitForm($form, $form_state);

      }
    };