<?php

class Text_Wiki_Render_Latex_Heading extends Text_Wiki_Render {
    var $conf = array(
        'article' => true
    );
    
    function token($options)
    {
        $article = $this->getConf('article');
        // get nice variable names (type, level)
        extract($options);

        if ($type == 'start') {
            switch ($level)
                {
                case '1':
                    if($article) {
                        return '\part*{';
                    } else {
                        return '\part{';
                    }
                case '2':
                    if($article) {
                        return '\section{';
                    } else {
                        return '\chapter{';
                    }
                case '3':
                    if($article) {
                        return '\subsection{';
                    } else {
                        return '\section{';
                    }
                case '4':
                    if($article) {
                        return '\subsubsection{';
                    } else {
                        return '\subsection{';
                    }
                case '5':
                    if($article) {
                        return '\paragraph{';
                    } else {
                        return '\subsubsection{';
                    }
                case '6':
                    if($article) {
                        return '\subparagraph{';
                    } else {
                        return '\paragraph{';
                    }
                }
        }
        
        if ($type == 'end') {
            return "}\n";
        }
    }
}
?>
