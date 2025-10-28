console.log('helloWorld');


const slides = document.querySelectorAll('.list .container');
let currentSlide = 0;

function showSlide(index) {
    slides.forEach(slide => {
        slide.classList.remove('active');
        slide.offsetWidth;
    });

    slides[index].classList.add('active');
}


const next = document.querySelector('.carouselBtn button:nth-child(2)');
next.addEventListener('click', () => {
    currentSlide++;
    if (currentSlide >= slides.length) {
        currentSlide = 0;
    }

    showSlide(currentSlide);
})


const prev = document.querySelector('.carouselBtn button:nth-child(1)');
prev.addEventListener('click', () => {
    currentSlide--;
    if (currentSlide < 0) {
        currentSlide = slides.length - 1;
    }
    showSlide(currentSlide);
})



function showAboutInfo(content, name) {

    const info = document.querySelectorAll('.info');


    info.forEach(infoContainer => {
        infoContainer.classList.remove('active');
        infoContainer.offsetWidth;
    });

    const profiles = document.querySelectorAll('.profile');
    profiles.forEach(profile => {
        profile.classList.remove('active');
        profile.offsetWidth;
    })

    document.getElementById(content).classList.add('active');
    document.getElementById(name).classList.add('active');


}

