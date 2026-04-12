<?php

namespace App\Enums;

enum Pagination: int
{
    case PAGE = 1;
    case PER_PAGE = 15;
    case MAX_PER_PAGE = 100;
}
