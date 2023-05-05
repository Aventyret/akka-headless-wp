<div class="Blurb-list-horizontal">
  <ul class="Blurb-list-horizontal__list">
  @foreach ($posts as $post)
    <li class="Blurb-list-horizontal__list-item Blurb-list-horizontal__list-item--{{ $size }}">
      <x-blurb :post="$post" :size="$size" />
    </li>
  @endforeach
  </ul>
</div>
