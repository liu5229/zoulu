<?php

//刮刮卡类

Class Scratch extends AbstractController {
    protected $sort;
    protected $num;
    protected $userId;
    protected $clock;
    protected $img;
    protected $imgList = array();

    public function __construct($num, $clock, $userId, $sort)
    {
        $this->num = $num;
        $this->clock = $clock;
        $this->userId = $userId;
        $this->sort = $sort;
        $this->img = $this->imgList[$this->num];
    }

}