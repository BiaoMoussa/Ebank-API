<?php

namespace repositories;

class Repository
{
    public static function getDbConnexion(){
       return $GLOBALS['db'];
    }

}