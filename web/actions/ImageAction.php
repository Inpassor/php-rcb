<?php

namespace rcb\web\actions;

class ImageAction extends \rcb\web\Action
{

    /**
     * @inheritdoc
     */
    public function run(array $parameters = []): void
    {
        $query = $this->app->request->getParams();
        print_r($query);
//        $image = new PgImage($config['image']);
//        $image->get()->show();
    }

}
