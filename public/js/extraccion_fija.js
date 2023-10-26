class SimpleCrudTableComponent
{
    constructor(selector, fields){
        this._selector = selector;
        this.fields = fields;
        this.data = [];
        this._html_by_ftype = {
            hidden: '<input type="hidden" name="{name}[]" class="form-control form-control-sm" value="{value}"/>',
            string: '<input type="text" name="{name}[]" class="form-control form-control-sm" value="{value}" {readonly}/>',
            largeString: '<textarea name="{name}[]" rows="10" class="form-control form-control-sm" {readonly}>{value}</textarea>',
            mediumString: '<textarea name="{name}[]" rows="5" class="form-control form-control-sm" {readonly}>{value}</textarea>',
            date: '<input type="date" name="{name}[]" class="form-control form-control-sm" value="{value}" {readonly} />',
            time: '<input type="text" name="{name}[]" class="form-control form-control-sm" value="{value}" placeholder="00:00:00" {readonly} />',
        };
        let _this = this;
        document.querySelector(`${this._selector} tbody`)
        .addEventListener("click", function(e){
            if(e.target.tagName === "BUTTON" && e.target.classList.contains("btn_delete")){
                let _index = e.target.attributes["data-index"].value;

                _this.deleteByEl(e.target.parentNode.parentNode);
            }
        });
    }

    getData()
    {
        let data = [];
        let rows = document.querySelectorAll(`${this._selector} tbody tr`);
        rows.forEach(element => {
            let row = {};
            element.childNodes.forEach(fieldElement => {
                let name = fieldElement.firstElementChild.name.replace("[]", "");
                let value = fieldElement.firstElementChild.value;
                if(name !== ""){
                    row[name] = value;
                }
            });
            data.push(row);
            // console.log(row);
        });
        return data;
    }

    add(values = {}){
        let _html_new = Object.keys(this.fields).map(field => {
            let type = this.fields[field].type;
            let _html = this._html_by_ftype[type];
            let _readonly = (this.fields[field].readonly??false) ? "readonly" : "";
            let _value = values[field] ?? '';
            _html = _html.replace("{name}", field);
            _html = _html.replace("{value}", _value);
            _html = _html.replace("{readonly}", _readonly);
            return `<td ${type === 'hidden' ? 'class="d-none"': ''}>${_html}</td>`;
        }).join("");
        let _options_html = `<td><button type="button" class="btn btn-secondary btn-sm btn_delete" data-index="${this.data.length}">-</button></td>`;
        let _body = document.querySelector(`${this._selector} tbody`);
        let old_html = _body.innerHTML;
        let new_tr = document.createElement("tr");
        new_tr.innerHTML = `<tr>${_html_new}${_options_html}</tr>`;
        _body.appendChild(new_tr);
    }

    delete(index){
        let _body = document.querySelector(`${this._selector} tbody`);
        for (var i = 0; i < _body.childNodes.length; i++) {
            if(i === index){
                _body.removeChild(_body.childNodes[i]);
            }
        }
    }

    deleteByEl(node){
        let _body = document.querySelector(`${this._selector} tbody`);
        _body.removeChild(node);
    }

    render(){

    }
}
