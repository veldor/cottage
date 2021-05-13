<?php

namespace app\models\interfaces;

interface CottageInterface
{
    public function getCottageNumber();
    public function getBaseCottageNumber();
    public function isMain();
    public function isIndividualTariff();
    public function save();
    public function haveAdditional();
    public function getSquare();
}