<?php

namespace app\models\interfaces;

interface CottageInterface
{
    public function getCottageNumber();
    public function getBaseCottageNumber();
    public function isMain();
}