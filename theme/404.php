<?php

use Timber\Timber;

/**
 * Default 404 Page.
 */
$context = Timber::get_context();

Timber::render('views/pages/404.twig', $context);
