<!-- ======= Header ======= -->
<header id="header" class="header fixed-top d-flex align-items-center">
  <div class="d-flex align-items-center justify-content-between">
    <a href="#" class="logo d-flex align-items-center">
      <img src="{{ asset('DashboardTemplates/assets/img/geodipa-logo.png') }}" alt="">
    </a>
    <i class="bi bi-list toggle-sidebar-btn"></i>
    <span class="d-md-none">{{ Auth::user()->nama_lengkap }}</span>
  </div><!-- End Logo -->

  @auth
  <nav class="header-nav ms-auto">
    <ul class="d-flex align-items-center">

      {{-- <li class="nav-item d-block d-lg-none">
        <a class="nav-link nav-icon search-bar-toggle" href="#">
          <i class="bi bi-search"></i>
        </a>
      </li><!-- End Search Icon--> --}}


      <li class="nav-item dropdown pe-3">
        <a class="nav-link nav-profile d-flex align-items-center pe-0" href="#" data-bs-toggle="dropdown">
          <i class="bi bi-person-circle"></i>
          <span class="d-none d-md-block dropdown-toggle ps-2">{{ Auth::user()->nama_lengkap }}</span>
        </a><!-- End Profile Image Icon -->

        <ul class="dropdown-menu dropdown-menu-end dropdown-menu-arrow profile">
          <li class="dropdown-header">
            <h6>{{ Auth::user()->nama_lengkap}}</h6>
            <span>{{ ucfirst(strtolower(Auth::user()->jabatan)) }}</span>
          </li>
          <li>
            <hr class="dropdown-divider">
          </li>

           <li>
            <form action="{{ route('logout') }}" method="POST" style="display: inline;">
              @csrf
              <button type="submit" class="dropdown-item d-flex align-items-center" style="border: none; background: none; width: 100%; text-align: left; cursor: pointer;">
                <i class="bi bi-box-arrow-right"></i>
                <span>Sign Out</span>
              </button>
            </form>
          </li>
        </ul><!-- End Profile Dropdown Items -->
      </li><!-- End Profile Nav -->

    </ul>
  </nav><!-- End Icons Navigation -->
  @endauth
</header><!-- End Header -->