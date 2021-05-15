<?php

namespace HexMakina\kadro\Controllers\Abilities;

require_once 'qivive/curlyb.php';

/**
 * handles importation, storage, retrieval and thumbnail making for all kinds of pictures (posters, still frames, covers)
**/
trait PictureManager
{
  use FileManager;
  
  public function filenames($replace_by_thumbs_if_exists=false) : array
  {
    $picture_directory = $this->build_path_to_directory();
    $thumbnail_directory = $picture_directory;

		if(!file_exists($picture_directory) && mkdir($picture_directory) === false)
			return [];

    $filenames = self::preg_scandir($picture_directory, '/^[0-9]+_[0-9]+\.[a-zA-Z]+/');// ID_SEQUENCENUMBER.ext

    sort($filenames);
    return $filenames;
  }

  public function filepathes($replace_by_thumbs_if_exists=false)
  {
    $filenames = $this->filenames($replace_by_thumbs_if_exists);
    $filepathes = [];
    foreach($filenames as $filename)
      $filepathes[] = $this->locate_thumbnail($filename);

    return $filepathes;
  }

  public function file_uris($replace_by_thumbs_if_exists=false)
  {
    $filenames = $this->filenames($replace_by_thumbs_if_exists);
    $uris = [];
    foreach($filenames as $filename)
      $uris[] = $this->locate_file($filename);

    dd($uris);
    return $uris;
  }

  private static function is_picture_file($filepath)
  {
    $picture_mime_to_gd_create_function = array(
      'image/jpeg' => 'imagecreatefromjpeg',
      'image/pjpeg' => 'imagecreatefromjpeg',
      'image/png' => 'imagecreatefrompng',
      'image/gif' => 'imagecreatefromgif',
    );
    $mime = mime_content_type($filepath);
    if(array_key_exists($mime, $picture_mime_to_gd_create_function))
      return true;
    return $mime;
  }


  public function upload()
  {
    if(!array_key_exists($this->get_type(), $_FILES))
      throw new \Exception($this->get_type()." not found in _FILES");

    if(!array_key_exists('size', $_FILES[$this->get_type()]) || $_FILES[$this->get_type()]['size'] == 0)
      throw new \Exception('uploaded file has no size');

    if(($file_type = self::is_picture_file($_FILES[$this->get_type()]['tmp_name'])) !== true)
      throw new \Exception('data sent is not an image but a '.$file_type.'');

    $filepath = $this->build_filename() . '.' . self::file_ext($_FILES[$this->get_type()]['name']);
    $filepath = $this->locate_file($filepath);

		if(file_exists($filepath))
      throw new \Exception($this->get_type()." new path '$filepath' already exists");

		if(copy($_FILES[$this->get_type()]['tmp_name'], $filepath) === false)
			throw new \Exception(" cant copy ".$_FILES[$this->get_type()]['name']." to ($filepath)");

    $this->make_thumbnail($filepath);
  }

  public function download($url)
  {
    $filepath = $this->build_filename() . '.' . self::file_ext($url);
    $filepath = $this->locate_file($filepath);

    \qivive\Curlyb::fetch($url, $filepath);

    $this->make_thumbnail($filepath);
  }

  public function make_thumbnail($filepath)
	{
    global $settings;

		$cover_iri = null;

		$mime_type = mime_content_type($filepath);
		switch($mime_type)
		{
			case 'image/jpeg':
			case 'image/pjpeg':
				$cover_iri = imagecreatefromjpeg($filepath);
			break;

			case 'image/png':
				$cover_iri = imagecreatefrompng($filepath);
			break;

			case 'image/gif':
				$cover_iri = imagecreatefromgif($filepath);
			break;
		}

		if(!is_null($cover_iri))
		{
			$width = imagesx( $cover_iri );
			$height = imagesy( $cover_iri );

			// calculate thumbnail size
      
			$new_width = $settings[get_class($this->pmi)::model_type()][$this->get_type()]['thumbnail']['width'];
			$new_height = floor( $height * ( $new_width / $width ) );

			// create a new temporary image
			$thumb_iri = imagecreatetruecolor($new_width, $new_height);

			// copy and resize old image into new image
			imagecopyresized( $thumb_iri, $cover_iri, 0, 0, 0, 0, $new_width, $new_height, $width, $height );

			// save thumbnail into a file
      imagejpeg($thumb_iri, $this->locate_thumbnail(pathinfo($filepath, PATHINFO_BASENAME)));
		}
	}

  public function remove_all()
  {
    $filenames = $this->filenames();

    foreach($filenames as $filename)
				$this->remove($filename);

    $directory = $this->build_path_to_directory();
		if(file_exists($directory) === true)
    {
      if(is_dir($directory) === false)
        throw new \Exception($this->get_type()."' directory '$directory' is not a directory");

   		if(rmdir($directory) === false)
  			throw new \Exception("rmdir($directory) failed like a bitch");
    }
    else trigger_error($this->get_type()." $directory doesn't exist", E_USER_WARNING);
  }

  public function remove($picture_filename)
  {
    // removing a picture, and maybe a thumbnail? build the $pathes array accordingly
    $pathes = [];
    $pathes[$this->get_type()] = $this->locate_file($picture_filename);
    $pathes[$this->get_type() . ' thumbnail'] = $this->locate_thumbnail($picture_filename);

    $deleted = [];
    $still_walking = [];
    foreach($pathes as $what => $path)
    {
      $error = null;
  		if(!file_exists($path))
        $error = 'file does not exist';
      elseif(unlink($path)===false)
        $error = 'unlink() failed';

      if(is_null($error))
        $deleted[]= $what;
      else
      {
        trigger_error(__FUNCTION__." '$picture_filename' ($what @ $path) impossible because ", E_USER_NOTICE);
        $still_walking[]=$what;
      }
    }
    return count($still_walking)===0;
  }

  public function locate_thumbnail($filename)
  {
    return $this->locate_file($this->picture_to_thumbnail_filename($filename));
  }

  public function picture_to_thumbnail_filename($picture_basename)
  {
    global $settings;
    return $settings['thumbnail']['file_prefix'].''.pathinfo($picture_basename, PATHINFO_FILENAME).'.jpg';
  }

  public static function file_info($absolute_path_to_picture, $what=null)
  {
    $ret = parent::file_info($absolute_path_to_picture, $what=null);

    if(is_array($ret))
    {
      $t = getimagesize($absolute_path_to_picture);
      $ret['width'] = $t[0];
      $ret['height'] = $t[1];
      $ret['mime'] = $t['mime'];
    }

    return $ret;
  }

  public static function picture_info($absolute_path_to_picture)
  {
    $image_size = getimagesize($absolute_path_to_picture);
    return "$image_size[0] * $image_size[1] (".$image_size['mime'].")";
  }


  public static function uri_for($item, $picture_type, $thumbnail=true)
  {
    global $settings;
    $pi_manager = new PictureManager($item, $picture_type);

		$pictures = $pi_manager->filenames();

    $item_model_type = get_class($item)::model_type();
    if(count($pictures)===0)
      return $settings[$item_model_type][$picture_type]['generic_picture'];

		if($settings[$item_model_type][$picture_type]['cycle_on_load'])
			$filename = $pictures[array_rand($pictures, 1)];
		else
			$filename = array_shift($pictures);

    return $thumbnail===true ? $pi_manager->locate_thumbnail($filename) : $pi_manager->locate_file($filename);
  }

  public function last_index()
  {
    $last_index = 0;
    if(count($filenames = $this->filenames()) > 0)
    {
      $last_filename = array_pop($filenames); // last cover name FIXME sort should be done here, check cost if sort already done
      if(preg_match('/[0-9]+\_([0-9]+)\.[a-z]+/', $last_filename, $last_index) !== 1)
        throw new \Exception("FAILED_COMPUTING_NEW_INDEX_USING_REGEX");

      $last_index = $last_index[1];
    }
    return intval($last_index);
  }

  protected function build_thumb_filename($picture_basename)
  {
    global $settings;
    return sprintf('%s%s.jpg', $settings['thumbnail']['file_prefix'], pathinfo($picture_basename, PATHINFO_FILENAME));
  }

  public function build_filename($index=null)
  {
    if(is_null($index))
      $index = $this->last_index()+1;

    return $this->pmi->id.'_'.sprintf("%'.09d", $index); // prepend bean id
  }

  public function move_to_top($selected_filename)
  {
    $files = $this->filenames();
    $filename_index = array_search($selected_filename, $files);

    $selected_filename_path = $this->locate_file($selected_filename);
    $new_filename_path = $this->locate_file($this->build_filename(0).'.'.pathinfo($selected_filename, PATHINFO_EXTENSION)); //move current to zero
    $this->rename($selected_filename_path, $new_filename_path);

    $selected_thumbname_path = $this->locate_file($this->picture_to_thumbnail_filename($selected_filename));
    $new_thumbname_path = $this->locate_file($this->build_thumb_filename($new_filename_path)); //move current to zero
    $this->rename($selected_thumbname_path, $new_thumbname_path);

    for($i=$filename_index-1; $i>=0; --$i)
    {
      $move_this = $this->locate_file($files[$i]);
      $to_that = $this->locate_file($files[$i+1]);
      $this->rename($move_this, $to_that);

      $move_this = $this->locate_file($this->picture_to_thumbnail_filename($files[$i]));
      $to_that = $this->locate_file($this->picture_to_thumbnail_filename($files[$i+1]));
      $this->rename($move_this, $to_that);

    }
    // ol' switcharoo
    $selected_filename_path = $new_filename_path;
    $new_filename_path = $this->locate_file($this->build_filename(1).'.'.pathinfo($selected_filename, PATHINFO_EXTENSION)); //move current to zero
    $this->rename($selected_filename_path, $new_filename_path);

    $selected_thumbname_path = $new_thumbname_path;
    $new_thumbname_path = $this->locate_file($this->picture_to_thumbnail_filename($this->build_filename(1).'.'.pathinfo($selected_filename, PATHINFO_EXTENSION))); //move current to zero
    $this->rename($selected_thumbname_path, $new_thumbname_path);

  }
}


?>
