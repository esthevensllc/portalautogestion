<li class="nav-item dropdown pr-4">
  <a class="nav-link text-white" data-toggle="dropdown" href="#" role="button" aria-haspopup="true" aria-expanded="false" style="position: relative;width: 35px;height: 35px;margin: 0 10px; justify-content:center; align-items: center; display: flex;">
    <span>{{$userIdentifier}}</span>
    {{-- <img class="img-avatar" src="{{ backpack_avatar_url(backpack_auth()->user()) }}" alt="{{$userIdentifier}}" onerror="this.style.display='none'" style="margin: 0;position: absolute;left: 0;z-index: 1;">
    <span class="backpack-avatar-menu-container" style="position: absolute;left: 0;width: 100%;background-color: #00a65a;border-radius: 50%;color: #FFF;line-height: 35px;font-size: 85%;font-weight: 300;">
      {{$userIdentifier ? mb_substr($userIdentifier, 0, 1, 'UTF-8') : 'A'}}
    </span> --}}
  </a>
  <div class="dropdown-menu {{ config('backpack.base.html_direction') == 'rtl' ? 'dropdown-menu-left' : 'dropdown-menu-right' }} mr-4 pb-1 pt-1">
    <a class="dropdown-item" href="{{ backpack_url('logout') }}"><i class="la la-lock"></i> {{ trans('backpack::base.logout') }}</a>
  </div>
</li>
