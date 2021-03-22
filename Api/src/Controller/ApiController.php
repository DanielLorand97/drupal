<?php

namespace Drupal\api\Controller;

use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\HttpFoundation\JsonResponse;
/**
 * Class ApiController.
 */
class ApiController extends ControllerBase {

     public function index() {
    return new JsonResponse([ 'data' => $this->getResults(), 'method' => 'GET', 'status'=> 200]);
  }

  public function getResults(){

    $json = file_get_contents("https://eventbook.ro/api/events/?festival_id=73");
   
    $obj = json_decode($json);;

  foreach($obj as $obj1 ){
    $this->checkTerm($obj1->section);
    $this->checkTerm($obj1->year);
   
    $values = \Drupal::entityQuery('node')
    ->condition('title',$obj1->title)
    ->execute();
   
     if (empty($values)) {
      $paths = $obj1->image;
      
      $pathinfo = pathinfo($paths);
      $image = file_get_contents( $paths);

      $file= file_save_data($image, 'public://pictures/' .$pathinfo['filename'].'.'.$pathinfo['extension'],FILE_EXISTS_RENAME);

 $entity = \Drupal::entityTypeManager()
                            ->getStorage('node')
                            ->create([
                                'type' => 'movies', 
                                'title' => $obj1->title,
                                'field_movie_' => $obj1->duration_seconds,
                                'body' => $obj1->performances[0]->display_date,
                                'field_movies_cast' => $obj1->cast,
                                'field_movies_image' =>array(
                                  array(
                                    'target_id' => $file->id(),
                                    'title' => $file->getFilename(),
                                    'filename' => $file->getFilename(),
                                  ),
                                ),
                               'field_movies_s' =>  $this->getTermID($obj1->section),
                               'field_movies_year' =>  $this->getTermID($obj1->year),
                                ]); 
  $entity->save();
  } 
        
  $result[]= [
        'title' =>  $obj1->title,
        'body' => $obj1->performances[0]->display_date,
        'section' => $obj1->section,
        'image' => $obj1->image_url,
        'year' => $obj1->year,
        'duration' =>  $obj1->duration_seconds,
        'cast' => $obj1->cast, 
    ];
  }
  return new JsonResponse([ 'data' => $result]);
}

public function checkTerm($term){
    if($term != null){
      $newterm = $term;
      $query = \Drupal::entityQuery('taxonomy_term');
      $query->condition('vid', "movies");
      $query->condition('name',  $newterm);
      $tids = $query->execute();
      if(!$tids){
        $term = \Drupal\taxonomy\Entity\Term::create([
            'vid' => 'movies',
            'name' =>  $newterm,
            ]);
        $term->save();
         return 1 ;
      }
    }else{
     return 0;
    }
  }
  public function getTermID($term){
    if($term != null){
       $term = \Drupal::entityTypeManager()->getStorage('taxonomy_term')
                                  ->loadByProperties(['name' => $term, 'vid' => 'movies']);
                                $term = reset($term);
                                $term_id = $term->id();
                                return $term_id;
    }else{
       $term = \Drupal::entityTypeManager()->getStorage('taxonomy_term')
        ->loadByProperties(['name' => 'unknown', 'vid' => 'movies']);
          $term = reset($term);
         $term_id = $term->id();
          return $term_id;
    }
  }


}
