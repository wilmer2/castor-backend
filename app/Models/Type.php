<?php

namespace App\Models;

use LaravelArdent\Ardent\Ardent;

class Type extends Ardent {

  private $localUrl = 'http://castor_backend';
  private $defaultImg = '/img/default/default_room.jpg';

  protected $fillable = ['title', 'description', 'increment', 'img_url'];

  public static $rules = [
    'title' => 'required|unique:types,title',
    'description' => 'required',
    'increment' => 'numeric'
  ];

  public static $customMessages = [
    'title.required' => 'El tipo es obligatorio',
    'title.unique' => 'Ya existe este tipo',
    'description.required' => 'La descripción es obligatoria',
    'increment.numeric' => 'El monto extra debe ser un número'
  ];

  public function rooms() {
    return $this->hasMany(Room::class);
  }

  public function beforeSave() {
    if($this->img_url == null) {
        $this->img_url = $this->localUrl.$this->defaultImg;
    }
  }

  public function countRooms($mime, $img) {
    return $this->rooms()
    ->count();
  }

  public function uploadImg($img, $mime) {
    $d = $this->dirExists();

    $dir  = $this->getDirName();
    $file = $this->getNameFile($mime);
    $path = $dir.$file;
    
    $imgUpload = str_replace('data:image/'.$mime.';base64,', '', $img);
    $imgUpload = str_replace(' ',  '+', $imgUpload);
    $data = base64_decode($imgUpload);

    file_put_contents($path, $data);

    $this->img_url = $this->getUrl($path);

    $this->save();

    return $d;
  }

  public function dirExists() {
    $dir = $this->getDirName();

    if(!file_exists($dir)) {
        mkdir($dir, 0777, true);
    } else {
        $imgUrlDefault = $this->localUrl.$this->defaultImg;

        if($this->img_url != $imgUrlDefault) {
            $path = $this->pathFile($this->img_url);
            $this->imgDrop($path);

            return 'delete';
        }

        return 'not delete';
    }
  }

  public function pathFile($imgUrl) {
    $dirPublic = public_path();
    $path = str_replace($this->localUrl, $dirPublic, $imgUrl);

    return $path;
  }

  public function imgDrop($path) {
    if(file_exists($path)) {
         unlink($path);
    }
  }

  public function getDirName() {
    return public_path().'/img/types/type'.$this->id;
  }

  public function getNameFile($mime) {
    $image = uniqid('/img', true);
    $image = str_replace('.', '', $image);
    $image = $image.$this->id.'.'.$mime;

    return $image;
  }

  public function getUrl($path) {
    $dirPublic = public_path();
    $url = str_replace($dirPublic, $this->localUrl, $path);

    return $url;
  }

}