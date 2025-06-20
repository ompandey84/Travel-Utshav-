let navbar = document.querySelector('.header .navbar');

document.querySelector('#menu-btn').onclick = () =>{
    navbar.classList.add('active');
}

document.querySelector('#nav-close').onclick = () =>{
    navbar.classList.remove('active');
}
let searchForm = document.querySelector('.search-form');

document.querySelector('#search-btn').onclick = () =>{
    searchForm.classList.add('active');
}

document.querySelector('#close-search').onclick = () =>{
    searchForm.classList.remove('active');
}
window.onscroll = () =>{
    navbar.classList.remove('active');
    if(window.scrollY > 0){
        document.querySelector('.header').classList.add('active');
    }
    else{
        document.querySelector('.header').classList.remove('active');
    }
};

window.onload = () =>{
    if(window.scrollY > 0){
        document.querySelector('.header').classList.add('active');
    }
    else{
        document.querySelector('.header').classList.remove('active');
    }
};


var swiper = new Swiper(".home-slider", {
    loop:true,
    grabCursor:true,
    navigation: {
      nextEl: ".swiper-button-next",
      prevEl: ".swiper-button-prev",
    },
  });

  var swiper = new Swiper(".product-slider", {
    loop:true,
    grabCursor:true,
    spaceBetween: 20,
    navigation: {
      nextEl: ".swiper-button-next",
      prevEl: ".swiper-button-prev",
    },
  
  breakpoints: {
    0: {
        slidesPerView: 1,
      },
    640: {
      slidesPerView: 2,
    },
    768: {
      slidesPerView: 3,
    },
    1024: {
      slidesPerView: 4,
      },
  },
});
var swiper = new Swiper(".review-slider", {
  loop:true,
  grabCursor:true,
  spaceBetween: 15,
  navigation: {
    nextEl: ".swiper-button-next",
    prevEl: ".swiper-button-prev",
  },

breakpoints: {
  0: {
      slidesPerView: 1,
    },
  640: {
    slidesPerView: 2,
  },
  768: {
    slidesPerView: 3,
  },
},
});
var swiper = new Swiper(".blogs-slider", {
  loop:true,
  grabCursor:true,
  spaceBetween: 10,
  navigation: {
    nextEl: ".swiper-button-next",
    prevEl: ".swiper-button-prev",
  },

breakpoints: {
  0: {
      slidesPerView: 1,
    },
  768: {
    slidesPerView: 2,
  },
  991: {
    slidesPerView: 3,
  },
},
});
document.addEventListener('DOMContentLoaded', function() {
  var swiper = new Swiper('.home-slider', {
      loop: true, // Enable continuous loop mode
      autoplay: {
          delay: 3000, // Delay between transitions (in milliseconds)
          disableOnInteraction: false, // Continue autoplay after user interactions
      },
      navigation: {
          nextEl: '.swiper-button-next',
          prevEl: '.swiper-button-prev',
      },
  });

  document.getElementById('subscribe-form').addEventListener('submit', function(event) {
      event.preventDefault(); // Prevent the default form submission

      const email = document.getElementById('email').value;
      const formData = new FormData();
      formData.append('email', email);

      fetch('subscribe.php', {
          method: 'POST',
          body: formData,
      })
      .then(response => response.json())
      .then(data => {
          if (data.success) {
              alert(data.message);
          } else {
              alert(data.message);
          }
      })
      .catch(error => {
          console.error('Error:', error);
          alert('An error occurred. Please try again.');
      });
  });

  function explore() {
      alert("Exploring new destinations coming soon!");
  }
});
