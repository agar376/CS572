import java.io.File;
import java.io.FileInputStream;
import java.io.FileOutputStream;
import java.io.IOException;
import java.io.PrintWriter;
import java.nio.file.Files;
import java.util.HashSet;
import java.util.Set;

import org.apache.tika.exception.TikaException;
import org.apache.tika.language.LanguageIdentifier;
import org.apache.tika.metadata.Metadata;
import org.apache.tika.parser.ParseContext;
import org.apache.tika.parser.html.HtmlParser;
import org.apache.tika.sax.BodyContentHandler;
import org.xml.sax.SAXException;

public class GenerateDictionary {

    @SuppressWarnings("deprecation")
    public static void main(final String[] args) throws IOException, SAXException, TikaException {

        File writeFile = new File(
                "/Users/ShobhitAgarwal/Dropbox/USC/Spring 17/CS 572/Homework Submissions/hw5/big.txt");
        Files.deleteIfExists(writeFile.toPath());

        PrintWriter writer = new PrintWriter(new FileOutputStream(writeFile, true));

        File dir = new File("/Users/ShobhitAgarwal/Downloads/NBCNewsData/NBCNewsDownloadData/");
        for (File file : dir.listFiles()) {
            FileInputStream inputstream = new FileInputStream(file);
            BodyContentHandler handler = new BodyContentHandler(-1);
            Metadata metadata = new Metadata();

            HtmlParser htmlparser = new HtmlParser();
            htmlparser.parse(inputstream, handler, metadata, new ParseContext());

            LanguageIdentifier identifier = new LanguageIdentifier(handler.toString());
            String language = identifier.getLanguage();
            if (language.equalsIgnoreCase("en")) {
                writer.println(handler.toString().replaceAll("\\s+", " "));
            }

        }
        writer.flush();
        writer.close();
    }
}