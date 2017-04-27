import java.io.BufferedReader;
import java.io.File;
import java.io.FileOutputStream;
import java.io.FileReader;
import java.io.PrintWriter;
import java.nio.file.Files;
import java.util.HashMap;
import java.util.HashSet;
import java.util.Map;
import java.util.Set;

import org.jsoup.Jsoup;
import org.jsoup.nodes.Document;
import org.jsoup.nodes.Element;
import org.jsoup.select.Elements;

/**
 * 
 * Author: Shobhit Agarwal <agar376@usc.edu>
 */
public class ExtractLinks {

    public static void main(String[] args) throws Exception {

        Map<String, String> fileUrlMap = new HashMap<String, String>();
        Map<String, String> urlFileMap = new HashMap<String, String>();

        BufferedReader br = new BufferedReader(
                new FileReader("/Users/ShobhitAgarwal/Downloads/NBCNewsData/mapNBCNewsDataFile.csv"));
        String line = null;

        while ((line = br.readLine()) != null) {
            String arr[] = line.split(",");
            if (arr.length > 1) {
                fileUrlMap.put(arr[0], arr[1]);
                urlFileMap.put(arr[1], arr[0]);
            }
        }
        br.close();

        File writeFile = new File(
                "/Users/ShobhitAgarwal/Dropbox/USC/Spring 17/CS 572/Homework Submissions/hw4/edgelist.txt");
        Files.deleteIfExists(writeFile.toPath());

        PrintWriter writer = new PrintWriter(new FileOutputStream(writeFile, true));

        File dir = new File("/Users/ShobhitAgarwal/Downloads/NBCNewsData/NBCNewsDownloadData/");
        Set<String> edges = new HashSet<String>();

        for (File file : dir.listFiles()) {
            Document doc = Jsoup.parse(file, "UTF-8", fileUrlMap.get(file.getName()));

            Elements links = doc.select("a[href]");
            // Elements media = doc.select("src");
            // Elements imports = doc.select("link[href]");
            // Elements href = doc.select("href");

            Elements allLinks = new Elements();
            allLinks.addAll(links);
            // allLinks.addAll(media);
            // allLinks.addAll(imports);
            // allLinks.addAll(href);

            for (Element el : allLinks) {
                String url = el.attr("abs:href").trim();
                if (urlFileMap.containsKey(url)) {
                    edges.add(file.getName() + " " + urlFileMap.get(url));
                }
            }
        }

        for (String s : edges) {
            writer.println(s);
        }

        writer.flush();
        writer.close();

    }
}
