<?php
/**
 * The default implementation to retrieve remote resources.
 *
 * @todo Shift all of the cache content to this class
 * @todo Consider an alternative implementation with HTTP_Request2 or something else.
 * @todo Introduce exceptions instead of exit()
 */
class Graphite_Retriever {

    public function __construct(Graphite $graph) {
        $this->graph = $graph;
    }

	/**
	 * Load the RDF from the given URI or URL.
	 */
	public function retrieve( $uri )
	{
        if( isset($this->graph->cacheDir) )
        {
            $filename = $this->graph->cacheDir."/".md5( $this->graph->removeFragment( $uri ) );

            if( !file_exists( $filename ) || filemtime($filename)+$this->graph->cacheAge < time() )
            {
                # decache if out of date, even if we fail to re cache.
                if( file_exists( $filename ) ) { unlink( $filename ); }
                $url = $uri;
                $ttl = 16;
                $mime = "";
                $old_user_agent = ini_get('user_agent');
                ini_set('user_agent', "PHP\r\nAccept: application/rdf+xml");
                while( $ttl > 0 )
                {
                    $ttl--;
                    # dirty hack to set the accept header without using curl
                    if( !$rdf_fp = fopen($url, 'r') ) { break; }
                    $meta_data = stream_get_meta_data($rdf_fp);
                    $redir = 0;
                    if( @!$meta_data['wrapper_data'] )
                    {
                        fclose($rdf_fp);
                        continue;
                    }
                    foreach($meta_data['wrapper_data'] as $response)
                    {
                        if (substr(strtolower($response), 0, 10) == 'location: ')
                        {
                            $newurl = substr($response, 10);
                            if( substr( $newurl, 0, 1 ) == "/" )
                            {
                                $parts = preg_split( "/\//",$url );
                                $newurl = $parts[0]."//".$parts[2].$newurl;
                            }
                            $url = $newurl;
                            $redir = 1;
                        }
                        if (substr(strtolower($response), 0, 14) == 'content-type: ')
                        {
                            $mime = preg_replace( "/\s*;.*$/","", substr($response, 14));
                        }
                    }
                    if( !$redir ) { break; }
                }
                ini_set('user_agent', $old_user_agent);
                if( $ttl > 0 && $mime == "application/rdf+xml" && $rdf_fp )
                {
                    # candidate for caching!
                    if (!$cache_fp = fopen($filename, 'w'))
                    {
                        echo "Cannot write file ($filename)";
                        exit;
                    }

                    while (!feof($rdf_fp)) {
                        fwrite( $cache_fp, fread($rdf_fp, 8192) );
                    }
                    fclose($cache_fp);
                }
                @fclose($rdf_fp);
            }

        }
        if( isset( $filename ) &&  file_exists( $filename ) )
        {
            return file_get_contents($filename);
        }

        return null;
	}
}