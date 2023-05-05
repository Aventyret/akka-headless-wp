<article class="{{$class_name}}">
  <a class="Blurb__link" href="{{ $post['url'] }}">
    @if($image_id && class_exists('\Akka_headless_wp_utils'))
    <figure class="Blurb__image-container">
4.      @php echo Akka_headless_wp_utils::internal_img_tag($image_id, ['sizes' => $image_sizes]) @endphp
    </figure>
    @endif
    <div class="Blurb__content">
      <h3 class="Blurb__title">{{$post['title']}}</h3>
      @if($post['description'])
      <div class="Blurb__description">{!! $post['description'] !!}</div>
      @endif
    </div>
  </a>
</article>
