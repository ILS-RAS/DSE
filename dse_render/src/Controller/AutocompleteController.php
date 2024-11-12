<?php

namespace Drupal\dse_render\Controller;

use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

class AutocompleteController extends ControllerBase {
    public function handleAutocomplete(Request $request) {
        $results = [];

        $session =\Drupal::request() -> getSession();
        $active_sources = $session -> get('dse_render.active_list');
        if (!$active_sources) { 
            $new_list = [];
                
            $conn = \Drupal::database();
            $query = $conn -> select('dse_render_datasources', 'd')
            -> fields('d', ['_id']);
            $result = $query -> execute() -> fetchAll();

            foreach ($result as $record) {
                $new_list[$record -> _id] = 1;
            }
            $session -> set('dse_render.active_list', $new_list);
        } 
            
        $_ids = array_keys($active_sources, 1);

        $input = $request -> query -> get('q');
        $input = $input . '%';

        if ($_ids) {
            if (strlen($input) >= 1 ){
                $conn = \Drupal::database();
            
                $query = $conn -> select('dse_render_vocables', 'v') 
                    -> fields('v', ['search_title'])
                    -> condition('source_id', $_ids, 'IN')
                    -> condition('search_title', $input, 'LIKE')
                    -> distinct()
                    -> range(0, 30);
            
                $result = $query -> execute() -> fetchAll();
                            
                if($result) {
                    foreach ($result as $record) {
                        $results[] = array(
                            'value' => $this -> t($record -> search_title),
                            'label' => $this -> t($record -> search_title)
                        );
                    }
                }
            }
        } else {
            $results[] = array(
                'value' => 'Nothing found',
                'label' => 'Nothing found',
            );
        }
        
        return new JsonResponse($results);
    }
}
