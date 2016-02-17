<?php
/***************************************************************************
 *                                                                          *
 *   (c) 2004 Vladimir V. Kalynyak, Alexey V. Vinokurov, Ilya M. Shalnev    *
 *                                                                          *
 * This  is  commercial  software,  only  users  who have purchased a valid *
 * license  and  accept  to the terms of the  License Agreement can install *
 * and use this program.                                                    *
 *                                                                          *
 ****************************************************************************
 * PLEASE READ THE FULL TEXT  OF THE SOFTWARE  LICENSE   AGREEMENT  IN  THE *
 * "copyright.txt" FILE PROVIDED WITH THIS DISTRIBUTION PACKAGE.            *
 ****************************************************************************/

define('AREA', 'C');

require_once __DIR__ . '/init.php';

use Tygh\BlockManager\Block;
use Tygh\BlockManager\Grid;
use Tygh\BlockManager\RenderManager;


if (isset($_REQUEST['block_id'], $_REQUEST['snapping_id'], $_REQUEST['lang_code'])) {
    $lang_code = (string) $_REQUEST['lang_code'];
    $block_id = (int) $_REQUEST['block_id'];
    $snapping_id = (int) $_REQUEST['snapping_id'];

    $block = Block::instance()->getById($block_id, $snapping_id, array(), $lang_code);
    $parent_grid = Grid::getById($block['grid_id'], $lang_code);

    $content = RenderManager::renderBlock($block, $parent_grid, 'C', array(
        'esi_enabled' => false,
        'use_cache' => false,
        'parse_js' => false,
    ));

    header('Content-Type: text/html');
    header('X-ESI-Response: true');

    echo $content;

    exit;
}