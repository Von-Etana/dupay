const showMenu = (toggleId, navId)=>{
    const toggle = document. getElementById(toggleId),
    nav = document.getElementById(navId)

    if(toggle && nav){
        toggle.addEventListener('click' , ()=>{
            nav.classList.toggle('show')
        })
    }
}

showMenu('nav-toggle' , 'nav-menu' )


const navLink = document.querySelectorAll('.nav__link'),
    navMenu = document.getElementById('nav-menu')


    function linkAction(){
        navMenu.classList.remove('show')
    }

    navLink.forEach(n => n.addEventListener('click'
    , linkAction))


    const sections = document.querySelectorAll('section[id]')

    window.addEventListener('scroll' , scrollActive)

    function scrollActive(){
        const scrollY = window.pageYOffset

        sections.forEach(current=>{
            const sectionHeight = current.offsetHeight
            const sectionTop = current.offsetTop - 50
            sectionId = current.getAttribute('id')

            if(scrollY > sectionTop && scrollY <= sectionTop + sectionHeight){
                document.querySelector(".nav__menu a[href*="+ sectionId + "]").classList.add('active')

            }else {
                document.querySelector(".nav__menu a[href*="+ sectionId + "]").classList.remove('active')

            }
        })

    }

    window.onscroll = ()=>{
        const nav = document.getElementById('header')
        if(this.scrollY >=200) nav.classList.add('scroll-header');
        else nav.classList.remove('scroll-header')
    }



    // PRODUCT DETAIL ///////


    const sizes = document.querySelectorAll('.size__tallas');
    const colors = document.querySelectorAll('.product__color');
    const sneaker = document.querySelectorAll('.product-img');

    function changeSize(){
        sizes.forEach(size => size.classList.remove('active-1'));
        this.classList.add('active-1');
    }


function changeColor(){
    let productColor = this.getAttribute('product-color');
    let color = this.getAttribute('color');
    let sneakerColor = document.querySelector(`.product-img[color="${color}"]`);

    colors.forEach(c => c.classList.remove('active-2'));
    this.classList.add('active-2');

    document.documentElement.style.setProperty('--product-color', productColor);

    sneaker.forEach(s => s.classList.remove('shows'));
    sneakerColor.classList.add('shows')
}

sizes.forEach(size => size.addEventListener('click', changeSize));
colors.forEach(c => c.addEventListener('click', changeColor));













// product img



let modal = document.getElementById('modal')

let closeBtn = document.getElementById('modal-close')

closeBtn.onclick = () => {
	modal.style.display = 'none'
}

let moreImages = document.getElementsByClassName('more-images-item')

let previewImages = document.getElementsByClassName('img-preview')

Array.from(moreImages).forEach((el) => {
	el.onclick = () => {
		let imgItems = el.parentNode.getElementsByTagName('img')

		Array.from(imgItems).forEach((item, index) => {
			previewImages[index].src = item.src
		})

		let img = el.querySelector('img')
		modal.style.display = 'block'

		let modalContent = modal.querySelector('.modal-content')
		modalContent.src = img.src

		let temp = modalContent.cloneNode(true)
		modalContent.parentNode.replaceChild(temp, modalContent)
	}
})







    // FULL PAGE JS SLIDES ////////////















