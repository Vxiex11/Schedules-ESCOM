

document.addEventListener('DOMContentLoaded', function() {
const faders = document.querySelectorAll('.fade-in');

const appearOptions = {
    threshold: 0.3, // El 30% del elemento debe ser visible para que se active
    rootMargin: "0px 0px -50px 0px"
};

const appearOnScroll = new IntersectionObserver(function(entries, appearOnScroll) {
    entries.forEach(entry => {
    if (!entry.isIntersecting) 
    {
        return;
    } else 
    {
        entry.target.classList.add('visible');
        appearOnScroll.unobserve(entry.target);
    }});
    }, appearOptions);
    faders.forEach(fader => 
    {
      appearOnScroll.observe(fader);
    });
  });

