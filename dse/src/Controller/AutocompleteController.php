<?php

namespace Drupal\dse\Controller;

use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

class AutocompleteController extends ControllerBase {

    public function handleAutocomplete(Request $request) {
        $config = $this -> config('dse_api.settings') -> get('url_list');
        $sources = [];
        foreach ($config as $url) {
            if ($url['enabled'] = true) {
                $sources[] = $url['id'];
            }
        }

        $results = [];
        $input = $request -> query -> get('q');
        $input = $input . '%';

        if (strlen($input) >= 4 ) {
            if ($sources) {
                $response = \Drupal::database() 
                -> select('dse_vocables','v')
                -> fields('v', ['title'])
                -> condition('v.source_id', $sources, 'IN')
                -> condition('v.title', $input, 'LIKE')
                -> distinct()
                -> range(0, 15)
                -> execute()
                -> fetchAll();


                if($response) {
                    foreach ($response as $record) {
                        $results[] = array(
                            'value' => $this -> t($record -> title),
                            'label' => $this -> t($record -> title)
                        );
                    }
                }
        }
    }

        return new JsonResponse($results);
    }
}