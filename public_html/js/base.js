

            function loadXMLDoc(){
                if (window.XMLHttpRequest){
                    xmlhttp=new XMLHttpRequest();
                }else{
                    xmlhttp=new ActiveXObject("Microsoft.XMLHTTP");
                }
                xmlhttp.onreadystatechange=function(){
                    if (xmlhttp.readyState==4 && xmlhttp.status==200){
                        document.getElementById("myDiv").innerHTML=xmlhttp.responseText;
                    }
                }
                var timer = setInterval(function(){
                    xmlhttp.open("GET","?q=crawl&loglevel=error",true);
                    xmlhttp.send();
                }, 5000);
            }


            function countdownRedirect(V3RefUrl, V3CounterMsg,CntTimer){
                var TARG_ID = "myDiv";
                var DEF_MSG = "Redirecting...";
                if( ! V3CounterMsg ){
                    V3CounterMsg = DEF_MSG;
                }
                var e = document.getElementById(TARG_ID);
                if( ! e ){
                    throw new Error('"COUNTDOWN_REDIRECT" element id not found');
                }
                var cTicks = CntTimer;
//                var timer = setInterval(function(){
//                    if( cTicks ){
                        e.innerHTML = V3CounterMsg + ' <br><br>'+ --cTicks +' ';
//                    }else{
//                        clearInterval(timer);
//                        document.title = "Done... "+ ++cTicks;
//                    }
//                }, 1000);
            }
