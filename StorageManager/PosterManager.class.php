<?php


class PosterManagerException extends Exception
{
  public function __construct($message, $code = 0, Exception $previous = null) {
      parent::__construct($message, $code, $previous);
  }
}


class PosterManager extends PictureManager
{
  const FILENAME_REGEX = '/^[0-9]+_[0-9]+\.[a-zA-Z]+/'; // ID_SEQUENCENUMBER.ext

  function __construct($picture_manageable_item)
  {
    parent::__construct($picture_manageable_item, 'poster');
  }

}


