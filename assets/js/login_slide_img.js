

$(function(){
  let $slides = $('.slider-container img');
  let currentIndex = 0;
  const slideCount = $slides.length;

  function showSlide(index){
    $slides.stop(true, true).fadeOut(600);
    $slides.eq(index).stop(true, true).fadeIn(600);
  }

  $('#next').click(function(){
    currentIndex = (currentIndex + 1) % slideCount;
    showSlide(currentIndex);
  });

  $('#prev').click(function(){
    currentIndex = (currentIndex - 1 + slideCount) % slideCount;
    showSlide(currentIndex);
  });

  // Auto slide every 5 seconds
  setInterval(function(){
    currentIndex = (currentIndex + 1) % slideCount;
    showSlide(currentIndex);
  }, 5000);
});

