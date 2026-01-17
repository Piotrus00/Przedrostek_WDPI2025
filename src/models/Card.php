<?php

class Card
{
    private $id;
    private $title;
    private $description;
    private $image;

//    public function __construct($title, $description, $image, $id = null)
//    {
//        $this->title = $title;
//        $this->description = $description;
//        $this->image = $image;
//        $this->id = $id;
//    }

    public function getId()
    {
        return $this->id;
    }

    public function getTitle()
    {
        return $this->title;
    }

    public function getDescription()
    {
        return $this->description;
    }

    public function getImage()
    {
        return $this->image;
    }
}

?>