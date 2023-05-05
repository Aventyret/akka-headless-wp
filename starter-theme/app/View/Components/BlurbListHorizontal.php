<?php

namespace App\View\Components;

use Roots\Acorn\View\Component;

class BlurbListHorizontal extends Component
{
    /**
     * The post.
     *
     * @var string
     */
    public $posts;

    /**
     * The size of the blurb.
     *
     * @var string
     */
    public $size;

    /**
     * Create the component instance.
     *
     * @param  array  $posts
     * @return void
     */
    public function __construct($posts, $size)
    {
        $this->posts = $posts;
        $this->size = $size;
    }

    /**
     * Get the view / contents that represent the component.
     *
     * @return \Illuminate\View\View|string
     */
    public function render()
    {
        return $this->view("components.blurb-list-horizontal");
    }
}
