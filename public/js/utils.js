

const utils = {
    downloadFile: (blob, fileName, charset = 'UTF-8') => {
        let blob_resp;
        if(charset === 'UTF-8'){
            blob_resp = new Blob(["\ufeff", blob], {encoding:"UTF-8",type:"text/plain;charset=UTF-8"});
        }else{
            blob_resp = new Blob([blob]);
        }
        const url = window.URL.createObjectURL(blob_resp)
        const link = document.createElement('a')
        link.href= url
        link.setAttribute('download', fileName);
        document.body.appendChild(link);
        link.click();
    
        link.parentNode.removeChild(link);
    },
    downloadBlob: (blob, fileName) => {
        const url = window.URL.createObjectURL(blob)
        const link = document.createElement('a')
        link.href= url
        link.setAttribute('download', fileName);
        document.body.appendChild(link);
        link.click();
    
        link.parentNode.removeChild(link);
    },
    downloadHandler: async (config, e) => {
        if (typeof e.preventDefault === 'function') {
            e.preventDefault();
        }
        if(config['btn_export'] === undefined){
            config['btn_export'] = '.btn_export';
        }

        let requestOptions = config['requestOptions'] ?? {};

        const button = document.querySelector(config['btn_export']);
        const loader_component = document.querySelector('.loader_component');
        button.disabled = true;
        button.innerHTML = 'Cargando ...';
        loader_component.style.display = 'block';
        try {
            const data = new FormData(e.target);
            const response = await utils.fetch(config.url, {method: 'POST', body: data, ...requestOptions});

            if(!response.ok){
                const _content_type = response.headers.get('Content-Type')??"";
                if(_content_type.includes("application/json")){
                    const json_response = await response.json();
                    throw new Error(json_response.message);
                }else{
                    throw new Error(response.statusText);
                }
            }
            
            //console.log(blob);
            let filename = 'reporte';
            const content_disp = response.headers.get('Content-Disposition');
            const header_parts = (content_disp??"").replaceAll('"', '').split(";");
            header_parts.forEach(row => {
                if(row.split("=")[1] !== undefined){
                    filename = row.split("=")[1];
                }
            });
            
            const blob_text = await response.blob();
            if(filename.includes('.zip') || filename.includes('.xls')){
                utils.downloadFile(blob_text, filename, 'default');
            }else{
                utils.downloadFile(blob_text, filename);
            }
        } catch (error) {
            console.log(error);
            alert(error.message);
            if(config['errorCallback']){
                await config['errorCallback'](error);
            }
        }
        button.disabled = false;
        button.innerHTML = 'Descargar';
        loader_component.style.display = 'none';
    },
    fetchAuthMiddleware: (response) => {
        if(response.status === 0){
            alert("El tiempo de sesión ha terminado iniciar sesión nuevamente");
            window.location.href = BASE_URL;
            const myPromise = new Promise((resolve, reject) => {
                setTimeout(() => { resolve("foo"); }, 10000);
            });
            return myPromise;
        }
        return response;
    },
    fetchErrorMiddleware: async (response) => {
        if(!response.ok){
            const _content_type = response.headers.get('Content-Type')??"";
            if(_content_type.includes("application/json")){
                const json_response = await response.json();
                throw new Error(JSON.stringify(json_response));
            }else{
                throw new Error(response.statusText);
            }
        }
        return response;
    },
    fetch: (...args) => {
        console.log(args);
        if(args[1] !== undefined){
            args[1] = {...args[1], redirect: 'manual'};
        }else{
            args[1] = {redirect: 'manual'};
        }
        return fetch(...args).then(utils.fetchAuthMiddleware);
    }
};