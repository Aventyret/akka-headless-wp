<?php

namespace App\View\Components;

use Roots\Acorn\View\Component;

class Testimonial extends Component
{
    /**
     * The size to display
     *
     * @var string
     */
    public $size;

    /**
     * The testimonial name (post title).
     *
     * @var string
     */
    public $name;

    /**
     * The testimonial business title.
     *
     * @var string
     */
    public $business_title;

    /**
     * The testimonial image id.
     *
     * @var integer
     */
    public $image_id;

    /**
     * The text to show (prop if provided, otherwise text from post).
     *
     * @var string
     */
    public $text;

    /**
     * Create the component instance.
     *
     * @param  object  $post
     * @return void
     */
    public function __construct($post, $size, $text)
    {
        $this->name = $post->post_title;
        $this->business_title = get_field(
            "testimonial_business_title",
            $post->ID
        );
        $this->image_id = get_post_thumbnail_id($post->ID);
        $this->size = $size;
        if ($text) {
            $this->text = $text;
        } else {
            $this->text = get_field("testimonial_default_text", $post->ID);
        }
    }

    /**
     * Get the view / contents that represent the component.
     *
     * @return \Illuminate\View\View|string
     */
    public function render()
    {
        return $this->view("components.testimonial");
    }
}
