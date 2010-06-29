/*
 * Copyright (C) 2010 Softao.Org
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 * http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */
package org.softao.jassandra;

import java.nio.ByteBuffer;
import java.nio.charset.Charset;
import java.util.Arrays;

import org.safehaus.uuid.UUID;
import org.softao.jassandra.utils.Charsets;

/**
 * The byte array value used in Cassandra.
 * <p>
 * This class is Immutable.
 */
public class ByteArray {
	/** Empty bytes value with zero length bytes array. */
	public static final ByteArray EMPTY = new ByteArray(new byte[0]);

	/**
	 * Creates a BytesValue initially containing the specified value.
	 * 
	 * @param value
	 *            the value
	 * @return a BytesValue initially containing the specified value
	 */
	public static ByteArray ofBytes(byte[] value) {
		return new ByteArray(value);
	}

	/**
	 * Creates a BytesValue initially containing the specified value.
	 * 
	 * @param value
	 *            the value
	 * @return a BytesValue initially containing the specified value
	 */
	public static ByteArray ofLong(long value) {
		return new ByteArray(long2Bytes(value));
	}

	/**
	 * Creates a BytesValue initially containing the specified value.
	 * 
	 * @param value
	 *            the value
	 * @return a BytesValue initially containing the specified value
	 */
	public static ByteArray ofUUID(UUID value) {
		return new ByteArray(value.toByteArray());
	}

	/**
	 * Creates a BytesValue initially containing the specified value.
	 * 
	 * @param value
	 *            the value
	 * @param charset
	 *            the charset
	 * @return a BytesValue initially containing the specified value
	 */
	public static ByteArray ofString(String value, Charset charset) {
		return new ByteArray(value.getBytes(charset));
	}

	/**
	 * A convenience method for encoding the string with {@link Charsets#UTF_8}.
	 * 
	 * @param value
	 * @return a BytesValue initially containing the specified value
	 */
	public static ByteArray ofUTF8(String value) {
		return new ByteArray(value.getBytes(Charsets.UTF_8));
	}

	/**
	 * A convenience method for encoding the string with
	 * {@link Charsets#US_ASCII}.
	 * 
	 * @param value
	 * @return a BytesValue initially containing the specified value
	 */
	public static ByteArray ofASCII(String value) {
		return new ByteArray(value.getBytes(Charsets.US_ASCII));
	}

	/**
	 * Long2 bytes.
	 * 
	 * @param value
	 *            the value
	 * @return the byte[]
	 */
	private static byte[] long2Bytes(long value) {
		ByteBuffer byteBuffer = ByteBuffer.allocate(8);
		byteBuffer.putLong(value);
		return byteBuffer.array();
	}

	/**
	 * Bytes2 long.
	 * 
	 * @param value
	 *            the value
	 * @return the long
	 */
	private static long bytes2Long(byte[] value) {
		ByteBuffer byteBuffer = ByteBuffer.allocate(8);
		byteBuffer.put(value);
		byteBuffer.rewind();
		return byteBuffer.getLong();
	}

	/** The m value. */
	private final byte[] mValue;

	/**
	 * Instantiates a new bytes value.
	 * 
	 * @param value
	 *            the value
	 */
	public ByteArray(byte[] value) {
		mValue = value;
	}

	/**
	 * To byte array.
	 * 
	 * @return the value
	 */
	public byte[] toByteArray() {
		return Arrays.copyOf(mValue, mValue.length);
	}

	/**
	 * To long.
	 * 
	 * @return the long value
	 */
	public long toLong() {
		return bytes2Long(mValue);
	}

	/**
	 * To uuid.
	 * 
	 * @return the UUID value
	 */
	public UUID toUUID() {
		return new UUID(mValue);
	}

	/**
	 * To string.
	 * 
	 * @param charset
	 *            the charset
	 * @return the string value
	 */
	public String toString(Charset charset) {
		return new String(mValue, charset);
	}

	/**
	 * Hash code.
	 * 
	 * @return the int
	 */
	@Override
	public int hashCode() {
		return Arrays.hashCode(mValue);
	}

	/**
	 * Equals.
	 * 
	 * @param obj
	 *            the obj
	 * @return true, if successful
	 */
	@Override
	public boolean equals(Object obj) {
		if (obj == null) {
			return false;
		}
		if (!(obj instanceof ByteArray)) {
			return false;
		}
		return Arrays.equals(mValue, ((ByteArray) obj).mValue);
	}
}
