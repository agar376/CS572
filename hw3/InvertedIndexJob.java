import java.io.IOException;
import java.util.StringTokenizer;
import java.util.Map;
import java.lang.Integer;
import java.lang.Long;
import java.util.Set;
import java.util.HashMap;

import org.apache.commons.logging.Log;
import org.apache.commons.logging.LogFactory;

import org.apache.hadoop.conf.Configuration;
import org.apache.hadoop.fs.Path;
import org.apache.hadoop.io.Text;
import org.apache.hadoop.io.MapWritable;
import org.apache.hadoop.io.IntWritable;
import org.apache.hadoop.io.Writable;
import org.apache.hadoop.mapreduce.Job;
import org.apache.hadoop.mapreduce.Mapper;
import org.apache.hadoop.mapreduce.Reducer;
import org.apache.hadoop.mapreduce.lib.input.FileInputFormat;
import org.apache.hadoop.mapreduce.lib.output.FileOutputFormat;

import org.apache.hadoop.mapreduce.InputFormat;
import org.apache.hadoop.mapreduce.InputSplit;
import org.apache.hadoop.mapreduce.lib.input.FileSplit;


public class InvertedIndexJob {
    private static final Log LOG = LogFactory.getLog(InvertedIndexJob.class);
    private static HashMap<String, HashMap<String, Integer>> index = new HashMap<String, HashMap<String, Integer>>();
    private static final Integer zero = new Integer(0);

    public static void logMessage(String[] fileContentArray) {
        LOG.warn(fileContentArray[0].toString());
        if (fileContentArray.length < 2) {
            LOG.error("Failed Map on " + fileContentArray[0].toString());
            System.out.println(fileContentArray[0].toString());
        }
        System.out.println(fileContentArray[0].toString());
    }

    public static class TokenizerMapper extends Mapper<Object, Text, Text, Text> {

        private Text word = new Text();

        public void map(Object key, Text value, Context context) throws IOException, InterruptedException {
            // try {
                String[] fileContentArray = value.toString().split("\t");
//                logMessage(fileContentArray);
                Text docId = new Text(fileContentArray[0].toString());
                StringTokenizer itr = new StringTokenizer(fileContentArray[1].toString());
                while (itr.hasMoreTokens()) {
                    word.set(itr.nextToken());
                    context.write(word, docId);
                }
            // } catch (Exception e) {
            //     Text filename = new Text(((FileSplit) context.getInputSplit()).getPath().toString());
            //     context.write(new Text("failed_file_mapping"), filename);
            // }
        }
    }

    static class MapWritableString extends MapWritable {
        @Override
        public String toString() {
            StringBuilder result = new StringBuilder();
            Set<Writable> keySet = this.keySet();

            for (Object key : keySet) {
                result.append(key.toString() + ":" + this.get(key) + " ");
            }
            return result.toString();
        }
    }

    public static class IndexReducer extends Reducer<Text, Text, Text, Text> {

        MapWritableString mw = new MapWritableString();

        public void reduce(Text key, Iterable<Text> values, Context context) throws IOException, InterruptedException {

            StringBuilder result = new StringBuilder();
            String keyString = key.toString();

            if (index.get(keyString) == null) {
                index.put(keyString, new HashMap<String, Integer>());
            }
            HashMap<String, Integer> reducedIndex = index.get(keyString);

            for (Text val : values) {
                String doc_key = val.toString();

                if (reducedIndex.get(doc_key) == null) {
                    reducedIndex.put(doc_key, zero);
                }
                int pVal = reducedIndex.get(doc_key);
                pVal++;
                reducedIndex.put(doc_key, new Integer(pVal));
                mw.put(val, new IntWritable(pVal));
            }
            index.put(keyString, reducedIndex);

            for (String k : reducedIndex.keySet()) {
                result.append(k.toString());
                result.append(":");
                result.append(reducedIndex.get(k).toString());
                result.append(" ");
            }

            context.write(key, new Text(result.toString()));
            // context.write(key, mw);
        }
    }

    public static void main(String[] args) throws Exception {
        Configuration conf = new Configuration();
        Job job = Job.getInstance(conf, "InvertedIndexJob");
        job.setJarByClass(InvertedIndexJob.class);
        job.setMapperClass(TokenizerMapper.class);
        // job.setCombinerClass(IndexReducer.class);
        job.setReducerClass(IndexReducer.class);
        job.setOutputKeyClass(Text.class);
        job.setOutputValueClass(Text.class);
        job.setMapOutputKeyClass(Text.class);
        job.setMapOutputValueClass(Text.class);
        FileInputFormat.addInputPath(job, new Path(args[0]));

        FileOutputFormat.setOutputPath(job, new Path(args[1]));
        System.exit(job.waitForCompletion(true) ? 0 : 1);
    }
}