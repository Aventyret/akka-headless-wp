<?php

namespace App\View\Components;

use Roots\Acorn\View\Component;

class Blurb extends Component
{
    /**
     * The post.
     *
     * @var string
     */
    public $post;

    /**
     * The blurb class name.
     *
     * @var string
     */
    public $class_name;

    /**
     * The image sizes attribute.
     *
     * @var string
     */
    public $image_sizes;

    /**
     * The image url.
     *
     * @var string
     */
    public $image_id;

    /**
     * Blurb size. 'small', 'medium' or 'large'
     *
     * @var string
     */
    public $size;

    /**
     * Create the component instance.
     *
     * @param  object  $post
     * @return void
     */
    public function __construct($post, $size = "medium")
    {
        $this->post = \Akka_headless_wp_content::get_post_in_archive(
            $post
        );

        $this->url = get_permalink($post);

        $this->image_id = null;
        if (has_post_thumbnail($post)) {
            $this->image_id = get_post_thumbnail_id($post);
        }

        if (!in_array(strtolower($size), ["small", "medium", "large"])) {
            throw new \Exception($size . " is not a valid blurb size");
        }

        $this->size = strtolower($size);
        $this->class_name = "Blurb Blurb--" . $size;
        $this->image_sizes =
            "(max-width: 980px) 100vw, (max-width: 1200px) 75vw, 33vw";
        if ($size == "medium") {
            $this->image_sizes =
                "(max-width: 720px) 100vw, (max-width: 1200px) 50vw, 20vw";
        }
        if ($size == "small") {
            $this->image_sizes =
                "(max-width: 720px) 100vw, (max-width: 980px) 33vw, 280px";
        }
    }

    /**
     * Get the view / contents that represent the component.
     *
     * @return \Illuminate\View\View|string
     */
    public function render()
    {
        return $this->view("components.blurb");
    }
}
