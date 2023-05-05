<?php

namespace App\View\Components;

use Roots\Acorn\View\Component;

class BlurbListFocus extends Component
{
    /**
     * The post.
     *
     * @var string
     */
    public $posts;

    /**
     * Create the component instance.
     *
     * @param  array  $posts
     * @return void
     */
    public function __construct($posts)
    {
        $this->posts = $posts;
    }

    /**
     * Get the view / contents that represent the component.
     *
     * @return \Illuminate\View\View|string
     */
    public function render()
    {
        return $this->view("components.blurb-list-focus");
    }
}
