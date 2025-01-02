$.prototype.uploadFile = function () {
    const uploadFile = ($el) => {
        let label = $el.find('label');
        let $input = $el.find('input[type=file]');
        let $filesList = $el.find('.files-list');
        $input.on('change', getValue, false);
        label.on('click', () => console.log('click'));
        console.log('input', $input)
        function getValue(){
            console.log(111111, this.files)
            for (let i = 0; i < this.files.length; i++) {
                let li = document.createElement('li');
                li.innerHTML = this.files[i].name;

                console.log(this.files[i].name)
                $filesList.appendChild(li);
            }
        }
    };

    for (let i = 0; i < this.length; i++) {
        uploadFile($(this[i]));
    }
};

$('[upload-file]').uploadFile();
