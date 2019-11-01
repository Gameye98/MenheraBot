<?php

/*
 * Copyright (C) 2019  <Cvar1984>
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License
 * as published by the Free Software Foundation; either version 2
 * of the License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */

class Faker {
    protected $name;
    protected $address;
    protected $phone;
    protected $card;
    protected $ccv;
    protected $date;

    function __construct() {
        $str = file_get_contents('http://namegenerators.org/fake-name-generator-us/');
        preg_match_all('/<div class="col2">(.*?)<\/div>/s',$str, $matches);
        self::setName(str_replace('</span>', '', str_replace('<span class="name">', '', $matches[1][3])));
        self::setAddress($matches[1][8]);
        self::setPhone($matches[1][9]);
        self::setCard(trim($matches[1][14]));
        self::setCcv($matches[1][16]);
        self::setDate($matches[1][15]);
    }

    private function setName($var)
    {
        $this->name=$var;
    }
    private function setAddress($var)
    {
        $this->address=$var;
    }
    private function setPhone($var)
    {
        $this->phone=$var;
    }
    private function setCard($var)
    {
        $this->card=$var;
    }
    private function setCcv($var)
    {
        $this->ccv=$var;
    }
    private function setDate($var)
    {
        $this->date=$var;
    }
    public function getName() {
        return $this->name;
    }
    public function getAddress() {
        return $this->address;
    }
    public function getPhone() {
        return $this->phone;
    }
    public function getCard() {
        return $this->card;
    }
    public function getCcv() {
        return $this->ccv;
    }
    public function getDate() {
        return $this->date;
    }

}
